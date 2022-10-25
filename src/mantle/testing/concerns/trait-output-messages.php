<?php
/**
 * Output_Messages trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use function Termwind\render;

/**
 * Messages for testing managed with Termwind.
 */
trait Output_Messages {
	/**
	 * Render a message to the console.
	 *
	 * @param string $prefix Prefix for the message.
	 * @param string $prefix_color Color for the prefix.
	 * @param string $message Message to render.
	 * @param string $message_color Color for the message.
	 * @param string $parent_classes Parent classes for the message.
	 * @return void
	 */
	protected static function message(
		string $prefix,
		string $prefix_color,
		string $message,
		string $message_color = 'white',
		string $parent_classes = '',
	) {
		render(
			sprintf(
				'<div class="%s">
					<div class="px-1 bg-%s text-%s">%s:</div>
					<span class="ml-1">%s</span>
				</div>',
				$parent_classes,
				$prefix_color,
				$message_color,
				$prefix,
				$message,
			)
		);
	}

	/**
	 * Output a info message to the console.
	 *
	 * @param string $message Message to output.
	 * @param string $prefix Prefix to output.
	 * @return void
	 */
	public static function info( string $message, $prefix = 'Install' ): void {
		static::message( $prefix, 'yellow-600', $message );
	}

	/**
	 * Output a success message to the console.
	 *
	 * @param string $message Message to output.
	 * @param string $prefix Prefix to output.
	 * @return void
	 */
	public static function success( string $message, $prefix = 'Install' ): void {
		static::message( $prefix, 'lime-600', $message );
	}

	/**
	 * Output a error message to the console.
	 *
	 * @param string $message Message to output.
	 * @param string $prefix Prefix to output.
	 * @return void
	 */
	public static function error( string $message, $prefix = 'Install' ): void {
		static::message( $prefix, 'red-800', $message, 'red-100', 'pt-1' );
	}

	/**
	 * Display a formatted code block.
	 *
	 * @link https://github.com/nunomaduro/termwind#code
	 *
	 * @param string|string[] $code Code to display.
	 * @return void
	 */
	public static function code( $code ): void {
		if ( is_array( $code ) ) {
			$code = implode( PHP_EOL, $code );
		}

		render( "<div class=\"my-1\"><code>{$code}</code></div>" );
	}
}
