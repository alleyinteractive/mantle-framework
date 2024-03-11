<?php
/**
 * Helper script to convert the tests/ folder to a PSR-4 folder structure.
 *
 * Moves tests/example/sub/File.php to tests/Example/Sub/FileTest.php. Only
 * convert the sub-part of the path (ignore anything before and including
 * tests/).
 *
 * phpcs:disable
 */

use Mantle\Support\Str;
use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\str;

require_once __DIR__ . '/../vendor/autoload.php';

foreach ( [ 'src', 'tests' ] as $base ) {
	$finder = ( new Finder() )
		->in( realpath( __DIR__ . "/../{$base}" ) )
		->directories()
		->notPath( '#fixtures|__snapshots__|template-parts#' );

	// Only rename the packages themselves.
	if ( 'src' === $base ) {
		$finder->depth( 1 );
	}

	$base = Str::trailing_slash( realpath( __DIR__ . "/../{$base}" ) );

	foreach ( $finder as $dir ) {
		$old_dir = $dir->getRealPath();

		if ( ! is_dir( $old_dir ) ) {
			continue;
		}

		$parts = str( $old_dir )->after( $base )->explode( '/' );

		$parts = $parts->map(
			fn ( string $part ) => str( $part )->studly()->value(),
		);

		$new_dir = $base . $parts->implode( '/' );

		dump( "Moving {$old_dir} to {$new_dir}" );

		shell_exec( "git mv {$old_dir} {$old_dir}-tmp" );
		shell_exec( "git mv {$old_dir}-tmp {$new_dir}" );
	}
}

echo "\nDONE!\n";

exit( 0 );
