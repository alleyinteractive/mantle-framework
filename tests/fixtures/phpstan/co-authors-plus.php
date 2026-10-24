<?php
/**
 * Minimal PHPStan stubs for the Co-Authors Plus plugin.
 *
 * The plugin is optional, so it is not installed when static analysis runs.
 * Only what the Mantle factories and tests use is declared here.
 *
 * phpcs:disable
 */

/**
 * @param int $post_id Post ID.
 * @return array<int, \WP_User|\stdClass>
 */
function get_coauthors( $post_id = 0 ) {
	return [];
}

class CoAuthors_Plus {}

class CoAuthors_Guest_Authors {}
