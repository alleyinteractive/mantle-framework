<?php
/**
 * View fixture echoing variables that collide with WordPress globals.
 *
 * @package Mantle
 */

echo 'id=' . ( $id ?? 'unset' ) . ';post=' . ( is_object( $post ?? null ) ? $post->post_title : 'unset' );
