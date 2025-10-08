<?php
/**
 * Nullable Core Objects functions
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Support\Helpers;

/**
 * Nullable wrapper for `get_post()`.
 *
 * @param int|\WP_Post|null $post   Post ID or post object.
 */
function get_post_object( $post = null ): ?\WP_Post {
	$object = \get_post( $post );

	return $object instanceof \WP_Post  ? $object : null;
}

/**
 * Nullable wrapper for `get_term()`.
 *
 * @param int|\WP_Term|object $term     Term ID, database object, or term
 *                                      object.
 * @param string              $taxonomy Taxonomy name that $term is part of.
 */
function get_term_object( $term, string $taxonomy = '' ): ?\WP_Term {
	$object = \get_term( $term, $taxonomy );

	return $object instanceof \WP_Term ? $object : null;
}

/**
 * Nullable wrapper for `get_term_by()`.
 *
 * @param string     $field    Either 'slug', 'name', 'id', or
 *                             'term_taxonomy_id'.
 * @param string|int $value    Search for this term value.
 * @param string     $taxonomy Taxonomy name. Optional, if $field is
 *                             'term_taxonomy_id'.
 */
function get_term_object_by( string $field, mixed $value, string $taxonomy = '' ): ?\WP_Term {
	$object = \get_term_by( $field, $value, $taxonomy );

	return $object instanceof \WP_Term ? $object : null;
}

/**
 * Nullable wrapper for `get_comment()`.
 *
 * @param \WP_Comment|string|int $comment Comment to retrieve.
 */
function get_comment_object( $comment ): ?\WP_Comment {
	$object = \get_comment( $comment );

	return ( $object instanceof \WP_Comment ) ? $object : null;
}

/**
 * Nullable wrapper for `get_userdata()`.
 *
 * @param \WP_User|int $user User ID/object.
 */
function get_user_object( $user ): ?\WP_User {
	if ( $user instanceof \WP_User ) {
		return $user;
	}

	$object = \get_userdata( (int) $user );

	return ( $object instanceof \WP_User ) ? $object : null;
}

/**
 * Nullable wrapper for `get_user_by()`.
 *
 * @param string     $field Either 'id', 'ID', 'slug', 'email', or 'login'.
 * @param int|string $value Search for this user value.
 */
function get_user_object_by( string $field, $value ): ?\WP_User {
	$object = \get_user_by( $field, $value );

	return ( $object instanceof \WP_User ) ? $object : null;
}

/**
 * Nullable wrapper for `get_site()`.
 *
 * @param \WP_Site|int|null $site Site to retrieve.
 */
function get_site_object( $site = null ): ?\WP_Site {
	$object = \get_site( $site );

	return ( $object instanceof \WP_Site ) ? $object : null;
}
