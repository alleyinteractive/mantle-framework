<?php

namespace Mantle\Framework\Testing\Database;

// Check if the unit test has disabled the use of SQLite.
if ( defined( 'MANTLE_USE_SQLITE' ) && ! MANTLE_USE_SQLITE ) {
	return;
}

if (! extension_loaded('pdo')) {
	echo "Unable to use SQLite, PDO extension not found.";
	return;
}

if (! extension_loaded('pdo_sqlite')) {
	echo "Unable to use SQLite, PDO SQLite driver not found.";
	return;
}

define( 'MANTLE_USE_SQLITE', true );

if ( ! defined( 'MANTLE_SQLITE_DSN' ) ) {
	define( 'MANTLE_SQLITE_DSN', 'sqlite::memory:' );
	// define( 'MANTLE_SQLITE_DSN', 'sqlite:' . );

	// $abspath = preg_replace( '#/wp-content/.*$#', '/wp-content/.db.sqlite', __DIR__ );
	// define( 'MANTLE_SQLITE_DSN', 'sqlite:' . $abspath );
}

define( 'MANTLE_SQLITE_DIR', __DIR__ );

$db_file_path = preg_replace( '#/wp-content/.*$#', '/wp-content/db.php', __DIR__ );
// var_dump(__DIR__ . '/db.php');
if ( ! file_exists( $db_file_path ) && ! copy( __DIR__ . '/db.php', $db_file_path ) ) {
	throw new \Exception( 'Error symlinking db.php file: ' . $db_file_path );
}
