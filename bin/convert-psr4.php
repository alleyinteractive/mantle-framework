<?php
/**
 * phpcs:disable
 */

use Symfony\Component\Finder\Finder;

use function Mantle\Support\Helpers\str;

require_once __DIR__ . '/../vendor/autoload.php';

$finder = ( new Finder() )
	->in( realpath( __DIR__ . '/../tests' ) )
	->name( '*.php' )
	->notName( [ 'bootstrap.php', 'sub-example.php', 'base-example.php' ] )
	->notPath( '#fixtures|__snapshots__|template-parts#' );

$index = [];
$pass  = false;

foreach ( $finder as $file ) {
	$filename = str( $file->getFilename() )->lower();

	if ( $filename->startsWith( 'test-' ) ) {
		$new_filename = $filename
			->after( 'test-' )
			->before( '.php' )
			->studly()
			->append( 'Test.php' )
			->replace( 'Wordpress', 'WordPress' );

		$old_class_name = $filename
			->before( '.php' )
			->studlyUnderscore()
			->replace( 'Wordpress', 'WordPress' );

		$new_class_name = $filename
			->after( 'test-' )
			->before( '.php' )
			->studly()
			->append( 'Test' )
			->replace( 'Wordpress', 'WordPress' );
	} else {
		foreach ( [ 'trait', 'class' ] as $type ) {
			if ( ! $filename->startsWith( "{$type}-" ) ) {
				continue;
			}

			$new_filename = $filename
				->after( "{$type}-" )
				->before( '.php' )
				->studly()
				->append( '.php' )
				->replace( 'Wordpress', 'WordPress' );

			$old_class_name = $filename
				->after( "{$type}-" )
				->before( '.php' )
				->studlyUnderscore();

			$new_class_name = $filename
				->after( "{$type}-" )
				->before( '.php' )
				->studly()
				->replace( 'Wordpress', 'WordPress' );
		}
	}

	// Check if the file contains the class.
	$contents = str( file_get_contents( $file->getRealPath() ) );

	if ( ! $contents->contains( "class {$old_class_name->value()} ", true ) ) {
		echo $file->getRealPath() . ' does not contain the expected legacy class ' . $old_class_name->value() . PHP_EOL;

		$pass = false;

		continue;
	}

	$index[] = [
		[
			$file->getRealPath(),
			$old_class_name->value(),
		],
		[
			$file->getPath() . '/' . $new_filename->value(),
			$new_class_name->value(),
		],
	];
}

if ( ! $pass ) {
	echo "\n\nPlease fix the above errors before continuing.\n";

	exit( 1 );
}

echo 'Processing ' . count( $index ) . ' files...';

foreach ( $index as $item ) {
	[ $old, $new ] = $item;

	[ $old_file, $old_class ] = $old;
	[ $new_file, $new_class ] = $new;

	// Update the file with the new class name.
	file_put_contents(
		$old_file,
		str( file_get_contents( $old_file ) )->replace( "class {$old_class} ", "class {$new_class} ", false )->value(),
	);

	// Update the file name.
	if ( ! rename( $old_file, $new_file ) ) {
		echo "Failed to rename {$old_file} to {$new_file}.\n";

		exit( 1 );
	}

	echo "Updated {$file->getFilename()} to {$new_filename->value()}: ({$old_class_name}) -> ({$new_class_name})\n\n";
}

echo "\nDONE!\n";

exit( 0 );
