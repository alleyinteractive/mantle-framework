<?php
add_action( 'init', function() {
	echo 'THIS';
} );

add_action('init', function() {
	echo 'That';
} );

add_action(
	'init',
	function() {
		echo 'Another!';
	}
);

this_shouldnt_add_action( 'init', function() { } );

add_filter( 'the_filter', function( $var ) {
	$var = '1234';
	return $var;
}, 20 );


this_shouldnt_add_filter( 'the_filter', function() { } );
