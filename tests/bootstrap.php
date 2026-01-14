<?php
/**
 * Framework Tests Bootstrap
 *
 * @package Mantle
 */

namespace Mantle\Tests;

use Mantle\Testing\Installation_Manager;
use Mantle\Testing\Utils;

define( 'MANTLE_PHPUNIT_INCLUDES_PATH', __DIR__ . '/includes' );
define( 'MANTLE_PHPUNIT_FIXTURES_PATH', __DIR__ . '/fixtures' );
define( 'MANTLE_PHPUNIT_TEMPLATE_PATH', __DIR__ . '/template-parts' );

// Enable debugging flag for local development on the testing framework.
 define( 'MANTLE_TESTING_DEBUG', true );

// For WordPress VIP testing, pin Jetpack to a specific version.
if ( Utils::env_bool( 'MANTLE_INSTALL_VIP_MU_PLUGINS', false ) )  {
	define( 'VIP_JETPACK_PINNED_VERSION', '13.9' );
	define( 'WPCOM_VIP_JETPACK_LOCAL', true );
}

\Mantle\Testing\manager()
	->maybe_rsync_plugin()
	->when(
		Utils::env_bool( 'MANTLE_INSTALL_VIP_MU_PLUGINS', false ),
		fn ( Installation_Manager $manager ) => $manager->with_vip_mu_plugins()->before( function () use ( $manager ): void {
			if ( $manager->is_within_wordpress_install() ) {
				return;
			}

			// Copy plugins/jetpack to client-mu-plugins/jetpack to allow it to be pinned to a specific version.
			// VIP requires it to be placed in client-mu-plugins/jetpack.
			$retcode = 0;
			$output  = Utils::command(
				sprintf(
					'WP_CORE_DIR=%s mkdir $WP_CORE_DIR/wp-content/client-mu-plugins && cp -r $WP_CORE_DIR/wp-content/plugins/jetpack $WP_CORE_DIR/wp-content/client-mu-plugins/jetpack',
					Utils::shell_safe( $manager->get_installation_path() ),
				),
				$retcode,
			);

			if ( 0 !== $retcode ) {
				Utils::error( "Failed to copy Jetpack to client-mu-plugins: \n" . implode( "\n", $output ) );
				exit( $retcode );
			}
		} ),
	)
	->install_plugin( 'logger', 'https://github.com/alleyinteractive/logger/archive/refs/heads/develop.zip' )
	->install_plugins(
		[ 'byline-manager', 'https://github.com/alleyinteractive/byline-manager/archive/refs/heads/production.zip' ],
		[ 'jetpack', '13.9' ],
		'co-authors-plus',
	)
	->plugins( [
		'byline-manager/byline-manager.php',
		'co-authors-plus/co-authors-plus.php',
	] )
	->without_local_object_cache()
	->install();
