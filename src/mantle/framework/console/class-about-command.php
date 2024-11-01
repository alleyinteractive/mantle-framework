<?php
/**
 * About_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console;

use Mantle\Console\Command;
use Mantle\Framework\Manifest\Model_Manifest;
use Mantle\Framework\Manifest\Package_Manifest;

use function Mantle\Support\Helpers\collect;

/**
 * About Publish Command
 */
class About_Command extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'about';

	/**
	 * Command description.
	 *
	 * @var string
	 */
	protected $description = 'Display information about the current Mantle installation.';

	/**
	 * Sections of data for the command.
	 *
	 * @var array
	 */
	protected static $sections = [];

	/**
	 * Add piece of data to the command's output.
	 *
	 * @param callable|array<string, string> $data    Data to add.
	 * @param string                         $section Section to add the data to.
	 */
	public static function add( callable|array $data, string $section = 'Custom' ): void {
		static::$sections[ $section ][] = $data;
	}

	/**
	 * Display the about information.
	 */
	public function handle(): int {
		$this->gather_information();

		foreach ( static::$sections as $section => $data ) {
			$this->line( $this->colorize( $section, 'yellow' ) );
			$this->table(
				[
					'Key',
					'Value',
				],
				collect( $data )
					->map(
						fn ( $data ) => collect( is_callable( $data ) ? $data() : $data )
							->map( fn ( $value, $key ) => [ $key, $this->format_value( $value ) ] )
							->values()
							->all()
					)
					->flatten( 1 )
					->all()
			);
			$this->line( '' );
		}

		return Command::SUCCESS;
	}

	/**
	 * Process the sections of data and fill in the default sections.
	 */
	protected function gather_information(): void {
		global $wp_version;

		static::add(
			fn () => [
				'App Path'          => $this->container->get_app_path(),
				'Base Path'         => $this->container->get_base_path(),
				'Root URL'          => $this->container->get_root_url(),
				'Debug Mode'        => $this->container['config']->get( 'app.debug', false ),
				'Namespace'         => $this->container->get_namespace(),
				'Environment'       => $this->container->environment(),
				'Isolation Mode'    => $this->container->is_running_in_console_isolation(),
				'WordPress Version' => $wp_version ?? 'Unknown',
				'PHP Version'       => phpversion(),
			],
			'Environment',
		);

		static::add(
			fn () => [
				'Aliases'   => $this->container->make( Package_Manifest::class )->aliases() ?: '(none)',
				'Models'    => $this->container->make( Model_Manifest::class )->models() ?: '(none)',
				'Providers' => $this->container->make( Package_Manifest::class )->providers() ?: '(none)',
			],
			'Discovery',
		);

		static::add(
			fn () => [
				'Config Cached' => $this->container->is_configuration_cached(),
				'Events Cached' => $this->container->is_events_cached(),
			],
			'Cache',
		);

		static::add(
			fn () => [
				'Path' => config( 'assets.path' ),
				'URL'  => config( 'assets.url' ),
			],
			'Assets',
		);

		static::add(
			fn () => [
				'Compiled Path' => config( 'view.compiled' ),
			],
			'Views',
		);

		static::add(
			fn () => [
				'Filesystem' => config( 'filesystem.default' ),
				'Logging'    => config( 'logging.default' ),
				'Queue'      => config( 'queue.default' ),
			],
			'Drivers',
		);
	}

	/**
	 * Format a value for display.
	 *
	 * @param mixed $value Value to format.
	 * @return mixed Formatted value.
	 */
	protected function format_value( mixed $value ): mixed {
		if ( is_bool( $value ) ) {
			return $value ? $this->colorize( 'TRUE', 'green' ) : $this->colorize( 'FALSE', 'red' );
		}

		if ( is_array( $value ) ) {
			return implode( ', ', $value );
		}

		return $value;
	}
}
