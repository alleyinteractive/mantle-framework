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

function test_function() {
	add_action( 'init', __NAMESPACE__ . '\this_here' );
}

class Test_Class {
	public function __construct() {
		$this->add_hooks();
	}

	protected function add_hooks() {
		/**
		 * Some comments before.
		 */
		add_action(
			'init',
			/**
			 * Comment after.
			 */
			[ $this, 'on_init' ]
		);

		 /**
			* Some comments after.
			*/
	}
}
