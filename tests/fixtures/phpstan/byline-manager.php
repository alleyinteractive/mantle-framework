<?php
/**
 * Minimal PHPStan stubs for the Byline Manager plugin.
 *
 * The plugin is optional, so it is not installed when static analysis runs.
 * Only what the Mantle factories and tests use is declared here.
 *
 * phpcs:disable
 */

namespace Byline_Manager\Models {
	class Profile {
		public \WP_Post $post;
	}

	class TextProfileItem {
		/**
		 * @var array<string, mixed>
		 */
		public array $atts = [];
	}
}

namespace Byline_Manager {
	class Utils {
		/**
		 * @return array<int, \Byline_Manager\Models\Profile|\Byline_Manager\Models\TextProfileItem>
		 */
		public static function get_byline_entries_for_post( \WP_Post $post ): array {
			return [];
		}
	}
}
