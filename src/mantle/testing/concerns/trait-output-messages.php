<?php
/**
 * Output_Messages trait file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use ErrorException;
use NunoMaduro\Collision\Writer;
use Whoops\Exception\Inspector;

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

	/**
	 * Outputs a trace message with Collision/Whoops.
	 *
	 * @param string $message Message to output.
	 * @param array  $trace Trace to output.
	 * @return void
	 */
	public static function trace( string $message, array $trace ): void {
		// Identify the starting frame for the trace.
		$frame = collect( $trace )
			->filter( fn ( $item ) => false === strpos( $item['file'], 'phpunit/phpunit' ) )
			->last();

		$exception = new ErrorException(
			$message,
			E_USER_ERROR,
			E_USER_ERROR,
			$frame['file'],
			$frame['line'],
		);

		$output = new \Symfony\Component\console\Output\ConsoleOutput();

		$writer = ( new Writer() )->setOutput( $output );

		$writer->showTitle( false );

		$writer->ignoreFilesIn(
			[
				'/vendor\/pestphp\/pest/',
				'/vendor\/phpspec\/prophecy-phpunit/',
				'/vendor\/phpunit\/phpunit\/src/',
				'/vendor\/mockery\/mockery/',
				'/vendor\/laravel\/dusk/',
				'/vendor\/laravel\/framework\/src\/Illuminate\/Testing/',
				'/vendor\/laravel\/framework\/src\/Illuminate\/Foundation\/Testing/',
				'/vendor\/symfony\/framework-bundle\/Test/',
				'/vendor\/symfony\/phpunit-bridge/',
				'/vendor\/symfony\/dom-crawler/',
				'/vendor\/symfony\/browser-kit/',
				'/vendor\/symfony\/css-selector/',
				'/vendor\/alleyinteractive\/mantle-framework/',
				'/vendor\/mantle-framework/',
				'/vendor\/bin\/.phpunit/',
				'/bin\/.phpunit/',
				'/vendor\/bin\/simple-phpunit/',
				'/bin\/phpunit/',
				'/vendor\/coduo\/php-matcher\/src\/PHPUnit/',
				'/vendor\/sulu\/sulu\/src\/Sulu\/Bundle\/TestBundle\/Testing/',
			]
		);

		$writer->write( new Inspector( $exception ) );

		$output->writeln( '' );
	}
}
