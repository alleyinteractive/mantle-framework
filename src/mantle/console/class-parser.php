<?php
/**
 * Parser class file
 *
 * phpcs:disable Squiz.Commenting.FunctionComment.EmptyThrows
 *
 * @package Mantle
 */

namespace Mantle\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command Signature Parser
 */
class Parser {

	/**
	 * Parse the given console command definition into an array.
	 *
	 * @param  string $expression
	 * @return array<mixed>
	 *
	 * @throws \InvalidArgumentException
	 */
	public static function parse( $expression ): array {
		$name = static::name( $expression );

		if ( preg_match_all( '/\{\s*(.*?)\s*\}/', $expression, $matches ) && count( $matches[1] ) ) {
			return array_merge( [ $name ], static::parameters( $matches[1] ) ); // @phpstan-ignore-line argument.type
		}

		return [ $name, [], [] ];
	}

	/**
	 * Extract the name of the command from the expression.
	 *
	 * @param  string $expression
	 *
	 * @throws \InvalidArgumentException
	 */
	protected static function name( $expression ): string {
		if ( ! preg_match( '/[^\s]+/', $expression, $matches ) ) {
			throw new InvalidArgumentException( 'Unable to determine command name from signature.' );
		}

		return $matches[0];
	}

	/**
	 * Extract all of the parameters from the tokens.
	 *
	 * @param  array<string, string> $tokens
	 * @return array<mixed>
	 */
	protected static function parameters( array $tokens ): array {
		$arguments = [];

		$options = [];

		foreach ( $tokens as $token ) {
			if ( preg_match( '/-{2,}(.*)/', (string) $token, $matches ) ) {
				$options[] = static::parse_option( $matches[1] );
			} else {
				$arguments[] = static::parse_argument( $token );
			}
		}

		return [ $arguments, $options ];
	}

	/**
	 * Parse an argument expression.
	 *
	 * @param  string $token
	 */
	protected static function parse_argument( $token ): \Symfony\Component\Console\Input\InputArgument {
		[ $token, $description ] = static::extract_description( $token );

		if ( str_ends_with( (string) $token, '?*' ) ) {
			return new InputArgument( trim( (string) $token, '?*' ), InputArgument::IS_ARRAY, $description );
		}

		if ( str_ends_with( (string) $token, '*' ) ) {
			return new InputArgument( trim( (string) $token, '*' ), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description );
		}

		if ( str_ends_with( (string) $token, '?' ) ) {
			return new InputArgument( trim( (string) $token, '?' ), InputArgument::OPTIONAL, $description );
		}

		if ( preg_match( '/(.+)\=\*(.+)/', (string) $token, $matches ) ) {
			return new InputArgument( $matches[1], InputArgument::IS_ARRAY, $description, preg_split( '/,\s?/', $matches[2] ) );
		}

		if ( preg_match( '/(.+)\=(.+)/', (string) $token, $matches ) ) {
			return new InputArgument( $matches[1], InputArgument::OPTIONAL, $description, $matches[2] );
		}

		return new InputArgument( $token, InputArgument::REQUIRED, $description );
	}

	/**
	 * Parse an option expression.
	 *
	 * @param  string $token
	 */
	protected static function parse_option( $token ): \Symfony\Component\Console\Input\InputOption {
		[$token, $description] = static::extract_description( $token );

		$matches = preg_split( '/\s*\|\s*/', (string) $token, 2 );

		if ( isset( $matches[1] ) ) {
			$shortcut = $matches[0];
			$token    = $matches[1];
		} else {
			$shortcut = null;
		}

		if ( str_ends_with( (string) $token, '=' ) ) {
			return new InputOption( trim( (string) $token, '=' ), $shortcut, InputOption::VALUE_OPTIONAL, $description );
		}

		if ( str_ends_with( (string) $token, '=*' ) ) {
			return new InputOption( trim( (string) $token, '=*' ), $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description );
		}

		if ( preg_match( '/(.+)\=\*(.+)/', (string) $token, $matches ) ) {
			return new InputOption( $matches[1], $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description, preg_split( '/,\s?/', $matches[2] ) );
		}

		if ( preg_match( '/(.+)\=(.+)/', (string) $token, $matches ) ) {
			return new InputOption( $matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, $matches[2] );
		}

		return new InputOption( $token, $shortcut, InputOption::VALUE_NONE, $description );
	}

	/**
	 * Parse the token into its token and description segments.
	 *
	 * @param  string $token
	 * @return array<string>
	 */
	protected static function extract_description( $token ) {
		$parts = preg_split( '/\s+:\s+/', trim( $token ), 2 );

		return count( $parts ) === 2 ? $parts : [ $token, '' ];
	}
}
