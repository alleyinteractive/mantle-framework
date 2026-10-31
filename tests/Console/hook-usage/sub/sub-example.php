<?php
add_action( 'init', static function() {
	echo 'THIS';
} );

add_action('init', static function() {
	echo 'That';
} );

add_action(
	'init',
	static function() {
		echo 'Another!';
	}
);

this_shouldnt_add_action( 'init', static function() { } );

add_filter( 'the_filter', static function( $var ) {
	$var = '1234';
	return $var;
}, 20 );


this_shouldnt_add_filter( 'the_filter', static function() { } );
