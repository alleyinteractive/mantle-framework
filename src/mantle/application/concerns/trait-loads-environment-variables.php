<?php
/**
 * Loads_Environment_Variables trait file
 *
 * @package Mantle
 */

namespace Mantle\Application\Concerns;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Mantle\Application\Application;
use Mantle\Framework\Console\Kernel;
use Mantle\Support\Environment;

/**
 * Load Environment Variables from the site.
 *
 * @mixin \Mantle\Application\Application
 */
trait Loads_Environment_Variables {
	/**
	 * Load the configuration for the application.
	 *
	 * @todo Add cached config usage.
	 */
	public function load_environment_variables() {
		try {
			$this->create_dotenv( $this )->safeLoad();
		} catch ( InvalidFileException $e ) {
			if ( $this->is_running_in_console() ) {
				$kernel = new Kernel( $this );

				$kernel->log( __( 'The Mantle environment file is invalid!', 'mantle' ) );
				$kernel->log( $e->getMessage() );
				exit( 1 );
			} else {
				// Because this runs so early, the configuration hasn't been loaded and we
				// can't have a fancy error message.
				esc_html_e( 'The Mantle environment file is invalid!', 'mantle' );
				echo esc_html( PHP_EOL . $e->getMessage() );
				exit( 1 );
			}
		}
	}

	/**
	 * Create a Dotenv instance.
	 *
	 * @param Application $app Application instance.
	 * @return Dotenv
	 */
	protected function create_dotenv( Application $app ): Dotenv {
		return Dotenv::create(
			Environment::get_repository(),
			$this->get_environment_paths( $app ),
			$app->environment_file(),
		);
	}

	/**
	 * Retrieve the environment paths.
	 *
	 * To support multiple hosting environments, environmental files can live in
	 * places other than the root of the application since that would be
	 * read-able.
	 *
	 * @param Application $app Application instance.
	 * @return array
	 */
	protected function get_environment_paths( Application $app ): array {
		// Use the application path if set.
		$application_path = $app->environment_path();

		$dotenv_suffix = "/{$app->environment_file()}";

		if ( $application_path && file_exists( $application_path . $dotenv_suffix ) ) {
			return [ $application_path ];
		}

		$paths = [];

		if ( file_exists( $app->get_base_path() . $dotenv_suffix ) ) {
			$paths[] = $app->get_base_path();
		}

		if ( defined( 'WPCOM_VIP_PRIVATE_DIR' ) && WPCOM_VIP_PRIVATE_DIR && file_exists( WPCOM_VIP_PRIVATE_DIR . $dotenv_suffix ) ) {
			$paths[] = WPCOM_VIP_PRIVATE_DIR;
		} elseif ( defined( 'WP_CONTENT_DIR' ) && file_exists( WP_CONTENT_DIR . '/private' . $dotenv_suffix ) ) {
			$paths[] = WP_CONTENT_DIR . '/private';
		}

		return $paths;
	}
}
