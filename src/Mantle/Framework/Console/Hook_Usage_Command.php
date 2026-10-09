<?php
/**
 * Hook_Usage_Command class file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Support\Collection;
use Mantle\Support\Str;
use PhpToken;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function Mantle\Support\Helpers\collect;

/**
 * Hook Usage Command
 *
 * Search a set of PHP files for every place a hook is registered, removed, or
 * fired, including Mantle's Hookable method names and attributes.
 *
 * @phpstan-type HookUsage array{file: string, line: int, method: string}
 */
class Hook_Usage_Command extends Command {
	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Tabulate all the usage of a hook in the code base.';

	/**
	 * Command signature.
	 *
	 * @var string
	 */
	protected $signature = 'hook-usage {hook} {--search-path=} {--format=table}';

	/**
	 * Functions that reference a hook by name as their first argument.
	 *
	 * @var string[]
	 */
	public const HOOK_METHODS = [
		'add_action',
		'add_action_side_effect',
		'add_filter',
		'add_filter_side_effect',
		'remove_action',
		'remove_filter',
		'remove_all_actions',
		'remove_all_filters',
		'has_action',
		'has_filter',
		'did_action',
		'did_filter',
		'doing_action',
		'doing_filter',
		'do_action',
		'do_action_ref_array',
		'do_action_deprecated',
		'apply_filters',
		'apply_filters_ref_array',
		'apply_filters_deprecated',
	];

	/**
	 * Hookable attributes that register a method against a hook.
	 *
	 * @var string[]
	 */
	public const HOOK_ATTRIBUTES = [
		'Action',
		'Filter',
	];

	/**
	 * Directory names that are skipped when searching a directory.
	 *
	 * @var string[]
	 */
	public const IGNORED_DIRECTORIES = [
		'node_modules',
		'tests',
		'vendor',
	];

	/**
	 * Callback for the command.
	 */
	public function handle(): int {
		$paths = $this->get_paths();

		if ( $paths->is_empty() ) {
			$this->error( 'No valid search paths found.' );

			return Command::FAILURE;
		}

		$usage = $this->get_usage( (string) $this->argument( 'hook' ), $paths );

		if ( $usage->is_empty() ) {
			$this->error( 'No usage found.' );

			return Command::FAILURE;
		}

		$this->format_data(
			(string) $this->option( 'format', 'table' ),
			[
				'file',
				'line',
				'method',
			],
			$usage->all(),
		);

		return Command::SUCCESS;
	}

	/**
	 * Retrieve the usage of a hook across a set of paths.
	 *
	 * @param string                 $hook  Hook name.
	 * @param Collection<int,string> $paths Files or directories to search.
	 * @return Collection<int,HookUsage>
	 */
	public function get_usage( string $hook, Collection $paths ): Collection {
		return $paths
			->flat_map( $this->get_files( ... ) )
			->unique()
			->flat_map( fn ( string $file ): array => $this->read_file( $file, $hook ) )
			->sort( fn ( array $a, array $b ): int => [ $a['file'], $a['line'] ] <=> [ $b['file'], $b['line'] ] )
			->values();
	}

	/**
	 * Get the paths to search from the --search-path option.
	 *
	 * @return Collection<int,string>
	 */
	protected function get_paths(): Collection {
		$search_path = (string) $this->option( 'search-path' );

		$paths = '' === $search_path
			? [ defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : (string) getcwd() ]
			: explode( ',', $search_path );

		return collect( $paths )
			->map( fn ( string $path ): string => trim( $path ) )
			->filter( fn ( string $path ): bool => is_file( $path ) || is_dir( $path ) )
			->unique()
			->values();
	}

	/**
	 * Get the PHP files within a path.
	 *
	 * @param string $path File or directory.
	 * @return string[]
	 */
	protected function get_files( string $path ): array {
		if ( is_file( $path ) ) {
			return 'php' === pathinfo( $path, PATHINFO_EXTENSION ) ? [ (string) realpath( $path ) ] : [];
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveCallbackFilterIterator(
				new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS ),
				function ( SplFileInfo $current ): bool {
					if ( $current->isDir() ) {
						return ! str_starts_with( $current->getFilename(), '.' )
							&& ! in_array( $current->getFilename(), static::IGNORED_DIRECTORIES, true );
					}

					return 'php' === $current->getExtension();
				}
			)
		);

		$list = [];

		foreach ( $files as $file ) {
			/** @var SplFileInfo $file */
			$list[] = (string) $file->getRealPath();
		}

		return $list;
	}

	/**
	 * Read a file and extract the references to a hook inside of it.
	 *
	 * @param string $file File to parse.
	 * @param string $hook Hook name.
	 * @return array<int,HookUsage>
	 */
	protected function read_file( string $file, string $hook ): array {
		$contents = file_get_contents( $file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		if ( false === $contents || ! str_contains( $contents, $hook ) ) {
			return [];
		}

		$tokens = array_values(
			array_filter(
				PhpToken::tokenize( $contents ),
				fn ( PhpToken $token ): bool => ! $token->isIgnorable(),
			)
		);

		$references = [];

		foreach ( $tokens as $index => $token ) {
			$method = match ( true ) {
				$token->is( T_FUNCTION ) => $this->match_hookable_method( $tokens, $index, $hook ),
				$token->is( [ T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED ] ) => $this->match_hook_call( $tokens, $index, $hook ),
				default => null,
			};

			if ( null !== $method ) {
				$references[] = [
					'file'   => $file,
					'line'   => $token->line,
					'method' => $method,
				];
			}
		}

		return $references;
	}

	/**
	 * Match a hook function call or Hookable attribute that passes the hook as
	 * its first argument.
	 *
	 * @param PhpToken[] $tokens Significant tokens of the file.
	 * @param int        $index  Index of the name token.
	 * @param string     $hook   Hook name.
	 */
	protected function match_hook_call( array $tokens, int $index, string $hook ): ?string {
		$argument = $tokens[ $index + 2 ] ?? null;

		if (
			! ( $tokens[ $index + 1 ] ?? null )?->is( '(' )
			|| ! $argument?->is( T_CONSTANT_ENCAPSED_STRING )
			|| substr( $argument->text, 1, -1 ) !== $hook
			|| ! ( $tokens[ $index + 3 ] ?? null )?->is( [ ',', ')' ] )
		) {
			return null;
		}

		$name     = Str::after_last( $tokens[ $index ]->text, '\\' );
		$previous = $tokens[ $index - 1 ] ?? null;

		if ( in_array( $name, static::HOOK_ATTRIBUTES, true ) && $previous?->is( [ T_ATTRIBUTE, ',' ] ) ) {
			return "#[{$name}]";
		}

		// Skip method calls and declarations that share a hook function's name.
		if (
			in_array( $name, static::HOOK_METHODS, true )
			&& ! $previous?->is( [ T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW ] )
		) {
			return $name;
		}

		return null;
	}

	/**
	 * Match a public method declaration that Hookable registers against the hook.
	 *
	 * Mirrors the `on_{hook}`, `on_{hook}_at_{priority}`, `action__{hook}`,
	 * and `filter__{hook}` naming in {@see \Mantle\Support\Traits\Hookable}.
	 *
	 * @param PhpToken[] $tokens Significant tokens of the file.
	 * @param int        $index  Index of the function token.
	 * @param string     $hook   Hook name.
	 */
	protected function match_hookable_method( array $tokens, int $index, string $hook ): ?string {
		$name = $tokens[ $index + 1 ] ?? null;

		if ( ! $name?->is( T_STRING ) ) {
			return null;
		}

		$is_public = false;
		$i         = $index - 1;

		while ( isset( $tokens[ $i ] ) && $tokens[ $i ]->is( [ T_PUBLIC, T_STATIC, T_FINAL, T_ABSTRACT ] ) ) {
			$is_public = $is_public || $tokens[ $i ]->is( T_PUBLIC );
			--$i;
		}

		if ( ! $is_public ) {
			return null;
		}

		$method_hook = match ( true ) {
			str_starts_with( $name->text, 'on_' ) => substr( $name->text, 3 ),
			str_starts_with( $name->text, 'action__' ), str_starts_with( $name->text, 'filter__' ) => substr( $name->text, 8 ),
			default => null,
		};

		if ( null !== $method_hook && str_contains( $method_hook, '_at_' ) ) {
			$method_hook = Str::before_last( $method_hook, '_at_' );
		}

		return $method_hook === $hook ? $name->text : null;
	}
}
