<?php
/**
 * Block_Service_Provider class file
 *
 * @package Mantle
 */

namespace Mantle\Framework\Providers;

use Mantle\Framework\Blocks\Discover_Blocks;
use Mantle\Support\Service_Provider;

/**
 * Block Service Provider
 *
 * Provides the foundation for building Gutenberg blocks with Mantle.
 */
class Block_Service_Provider extends Service_Provider {
	/**
	 * Register the application's blocks.
	 *
	 * @return void
	 */
	public function register() {
		$this->app->booting(
			function() {
				collect( $this->get_blocks() )
				->each( fn ( string $block ) => ( new $block() )->register() );
			}
		);
	}

	/**
	 * Discover blocks for the application.
	 *
	 * @return array An array of block class names.
	 */
	public function get_blocks(): array {
		$blocks = collect( $this->discover_blocks_within() );

		$after_rejects = $blocks->reject( fn ( $dir ) => ! is_dir( $dir ) );

		$reduced = $after_rejects->reduce(
			fn ( array $discovered, string $directory ) => array_merge_recursive(
				$discovered,
				Discover_Blocks::within( $directory, $this->app->get_base_path() )
			),
			[]
		);

		return $reduced;
	}

	/**
	 * The array of locations to discover blocks within.
	 *
	 * @return array
	 */
	protected function discover_blocks_within(): array {
		return [
			$this->app->get_app_path( 'blocks' ),
		];
	}
}
