<?php
/**
 * Load_Environment_Variables class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Mantle\Console\Kernel;
use Mantle\Framework\Application;
use Mantle\Support\Environment;

/**
 * Load Environment Variables from a private .env file.
 */
class Load_Environment_Variables {
	/**
	 * Load the configuration for the application.
	 *
	 * @todo Add cached config usage.
	 *
	 * @param Application $app Application instance.
	 */
	public function bootstrap( Application $app ) {
		try {
			$this->create_dotenv( $app )->safeLoad();
		} catch ( InvalidFileException $e ) {
			$this->write_error_and_die( $app, $e );
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

		if ( $application_path ) {
			return [ $application_path ];
		}

		$paths = [
			$app->get_base_path(),
		];

		if ( defined( 'WPCOM_VIP_PRIVATE_DIR' ) && WPCOM_VIP_PRIVATE_DIR ) {
			$paths[] = WPCOM_VIP_PRIVATE_DIR;
		} elseif ( defined( 'WP_CONTENT_DIR' ) ) {
			$paths[] = WP_CONTENT_DIR . '/private';
		}

		return $paths;
	}

	/**
	 * Write the error information and exit.
	 *
	 * @param Application          $app Application instance.
	 * @param InvalidFileException $e Exception.
	 * @return void
	 */
	protected function write_error_and_die( Application $app, InvalidFileException $e ): void {
		if ( $app->is_running_in_console() ) {
			$kernel = new Kernel( $app );

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
