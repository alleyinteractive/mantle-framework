<?php

namespace Mantle\Framework\Testing\Database;

// Check if the unit test has disabled the use of SQLite.
if ( defined( 'MANTLE_USE_SQLITE' ) && ! MANTLE_USE_SQLITE ) {
	return;
}

if ( ! extension_loaded( 'pdo' ) ) {
	echo 'Unable to use SQLite, PDO extension not found.';
	return;
}

if ( ! extension_loaded( 'pdo_sqlite' ) ) {
	echo 'Unable to use SQLite, PDO SQLite driver not found.';
	return;
}

define( 'MANTLE_USE_SQLITE', true );
define( 'MANTLE_SQLITE_DIR', __DIR__ );

if ( ! defined( 'MANTLE_SQLITE_PATH' ) ) {
	define( 'MANTLE_SQLITE_PATH', preg_replace( '#/wp-content/.*$#', '/wp-content/.db.sqlite', __DIR__ ) );
}

$db_file_path = preg_replace( '#/wp-content/.*$#', '/wp-content/db.php', __DIR__ );
if ( ! copy( __DIR__ . '/db.php', $db_file_path ) ) {
	throw new \Exception( 'Error symlinking db.php file: ' . $db_file_path );
}

register_shutdown_function(
	function() use ( $db_file_path ) {
		if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) {
			return;
		}

		@unlink( $db_file_path );
		@unlink( MANTLE_SQLITE_PATH );
	}
);
