<?php
/**
 * Block_Service_Provider class file
 *
 * @package Mantle
 */

namespace Mantle\Blocks;

use Mantle\Blocks\Discover_Blocks;
use Mantle\Support\Service_Provider;

/**
 * Block Service Provider
 *
 * Provides the foundation for building Gutenberg blocks with Mantle.
 */
class Block_Service_Provider extends Service_Provider {
	/**
	 * Register the application's blocks.
	 */
	public function register(): void {
		$this->app->booting(
			fn () => collect( $this->get_blocks() )->each(
				fn ( string $block ) => ( new $block() )->register()
			)
		);
	}

	/**
	 * Discover blocks for the application.
	 *
	 * @return array An array of block class names.
	 */
	public function get_blocks(): array {
		return collect( $this->discover_blocks_within() )
			->reject( fn ( $dir ) => ! is_dir( $dir ) )
			->reduce(
				fn ( array $discovered, string $directory ) => array_merge_recursive(
					$discovered,
					Discover_Blocks::within( $directory, $this->app->get_base_path() )
				),
				[]
			);
	}

	/**
	 * The array of locations to discover blocks within.
	 */
	protected function discover_blocks_within(): array {
		return [
			$this->app->get_app_path( 'blocks' ),
		];
	}
}
