<?php
namespace Example;

// add_action( 'init', 'commented_out' );

/* add_filter( 'init', 'commented_out' ); */

$this->add_action( 'init', 'method_call' );

Some_Class::add_action( 'init', 'static_call' );

add_action( 'init_extra', 'different_hook' );

add_action( "init", 'double_quoted' );

\Mantle\Support\Helpers\add_action( 'init', 'fully_qualified' );

do_action( 'init' );

$value = apply_filters( 'the_filter', 'value' );

remove_action( 'init', 'removed' );

$message = "add_action( 'init', 'inside_a_string' )";

add_action( 'the/slashed/hook', 'slashed' );

if ( did_action( 'init' ) ) {}
