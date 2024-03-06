<?php
/**
 * Asset class file.
 *
 * @package Mantle
 */

namespace Mantle\Assets;

use Asset_Manager_Scripts;
use Asset_Manager_Styles;
use Mantle\Contracts\Assets\Load_Hook;
use Mantle\Contracts\Assets\Load_Method;

use function Mantle\Support\Helpers\hook_callable;

/**
 * Asset Manager
 */
class Asset {
	/**
	 * Asset style media.
	 *
	 * @var string
	 */
	protected $media;

	/**
	 * Enqueue on frontend.
	 */
	protected bool $frontend = true;

	/**
	 * Enqueue in the admin area.
	 */
	protected bool $admin = true;

	/**
	 * Enqueue in the block editor area.
	 */
	protected bool $block_editor = false;

	/**
	 * Constructor.
	 *
	 * @param string               $type        Asset type (script/style).
	 * @param string               $handle      Asset handle.
	 * @param string               $src         Asset URL.
	 * @param array<string>        $deps        Asset dependencies.
	 * @param array<string>|string $condition   Condition to load.
	 * @param string               $load_method Load method.
	 * @param string               $load_hook   Load hook.
	 * @param string|null          $version     Asset version.
	 * @param bool                 $infer_from_loader Infer the asset from the loader if the source is not provided.
	 */
	public function __construct(
		protected string $type,
		protected string $handle,
		protected ?string $src = null,
		protected array $deps = [],
		protected string|array $condition = 'global',
		protected string $load_method = Load_Method::SYNC,
		protected string $load_hook = Load_Hook::HEADER,
		protected ?string $version = null,
		bool $infer_from_loader = true,
	) {
		if ( ! $src && $infer_from_loader ) {
			$this->infer_from_asset_loader();
		}
	}

	/**
	 * Set an asset to load with async.
	 */
	public function async(): Asset {
		if ( Load_Method::DEFER === $this->load_method ) {
			$this->load_method = Load_Method::ASYNC_DEFER;
		} else {
			$this->load_method = Load_Method::ASYNC;
		}

		return $this;
	}

	/**
	 * Set an asset to load with async.
	 */
	public function sync(): Asset {
		$this->load_method = Load_Method::SYNC;
		return $this;
	}

	/**
	 * Defer a script.
	 */
	public function defer(): Asset {
		if ( Load_Method::ASYNC === $this->load_method || Load_Method::ASYNC_DEFER === $this->load_method ) {
			$this->load_method = Load_Method::ASYNC_DEFER;
		} else {
			$this->load_method = Load_Method::DEFER;
		}

		return $this;
	}

	/**
	 * Condition to load the asset.
	 *
	 * @param string|array $condition Condition to load.
	 */
	public function condition( $condition ): Asset {
		$this->condition = $condition;
		return $this;
	}

	/**
	 * Load the asset in the header.
	 */
	public function header(): Asset {
		$this->load_hook = Load_Hook::HEADER;
		return $this;
	}

	/**
	 * Load the asset in the footer.
	 */
	public function footer(): Asset {
		$this->load_hook = Load_Hook::FOOTER;
		return $this;
	}

	/**
	 * Load the asset on a specific hook.
	 *
	 * @param string $hook Hook to load on.
	 */
	public function hook( string $hook ): Asset {
		$this->load_hook = $hook;
		return $this;
	}

	/**
	 * Set the version of the asset.
	 *
	 * @param string|null $version Version to set.
	 */
	public function version( ?string $version ): Asset {
		$this->version = $version;
		return $this;
	}

	/**
	 * Set the asset dependencies.
	 *
	 * @param string[] $dependencies Dependencies to set.
	 */
	public function dependencies( array $dependencies ): Asset {
		$this->deps = $dependencies;
		return $this;
	}

	/**
	 * Add a dependency to the asset.
	 *
	 * @param string $dependency Dependency to add.
	 */
	public function add_dependency( string $dependency ): Asset {
		$this->deps[] = $dependency;
		return $this;
	}

	/**
	 * Set the asset handle.
	 *
	 * @param string $handle Handle to set.
	 */
	public function handle( string $handle ): Asset {
		$this->handle = $handle;
		return $this;
	}

	/**
	 * Set the asset URL.
	 *
	 * @param string $src URL to set.
	 */
	public function src( string $src = '' ): Asset {
		$this->src = $src;
		return $this;
	}

	/**
	 * Set the media to use for style assets.
	 *
	 * @param string $media Media to set.
	 */
	public function media( string $media = '' ): Asset {
		$this->media = $media;
		return $this;
	}

	/**
	 * Tell the asset whether or not to load on the frontend.
	 *
	 * @param bool $load True if this should load on the frontend of the site.
	 */
	public function frontend( bool $load ): Asset {
		$this->frontend = $load;
		return $this;
	}

	/**
	 * Tell the asset to only load on the front-end
	 */
	public function only_frontend(): Asset {
		return $this
			->frontend( true )
			->admin( false )
			->block_editor( false );
	}

	/**
	 * Tell the asset whether or not to load in the admin area.
	 *
	 * @param bool $load True if this should load in the admin area of the site.
	 */
	public function admin( bool $load ): Asset {
		$this->admin = $load;
		return $this;
	}

	/**
	 * Tell the asset to only load in the admin area.
	 */
	public function only_admin(): Asset {
		return $this
			->frontend( false )
			->admin( true )
			->block_editor( false );
	}

	/**
	 * Tell the asset whether or not to load in the block editor
	 *
	 * @param bool $load True if this should load in the block editor.
	 */
	public function block_editor( bool $load ): Asset {
		$this->block_editor = $load;
		return $this;
	}

	/**
	 * Tell the asset to only load in the block editor.
	 */
	public function only_block_editor(): Asset {
		return $this
			->frontend( false )
			->admin( false )
			->block_editor( true );
	}

	/**
	 * Register the asset with asset manager. If called before
	 * 'wp_enqueue_scripts', it will defer registration until then.
	 */
	public function register(): void {
		if ( $this->frontend ) {
			hook_callable( 'wp_enqueue_scripts', fn () => $this->register_asset() );
		}

		if ( $this->admin ) {
			hook_callable( 'admin_enqueue_scripts', fn () => $this->register_asset() );
		}

		if ( $this->block_editor ) {
			hook_callable( 'enqueue_block_editor_assets', fn () => $this->register_asset() );
		}
	}

	/**
	 * Register the asset with asset manager.
	 *
	 * @return void
	 */
	protected function register_asset() {
		if ( 'script' === $this->type ) {
			Asset_Manager_Scripts::instance()->add_asset(
				[
					'handle'      => $this->handle,
					'src'         => $this->src,
					'deps'        => $this->deps,
					'condition'   => $this->condition,
					'load_method' => $this->load_method,
					'version'     => $this->version,
					'load_hook'   => $this->load_hook,
				]
			);
		} elseif ( 'style' === $this->type ) {
			Asset_Manager_Styles::instance()->add_asset(
				[
					'handle'      => $this->handle,
					'src'         => $this->src,
					'deps'        => $this->deps,
					'condition'   => $this->condition,
					'load_method' => $this->load_method,
					'version'     => $this->version,
					'load_hook'   => $this->load_hook,
					'media'       => $this->media,
				]
			);
		}
	}

	/**
	 * Register the asset on destruction.
	 *
	 * Allows for a fluent interface call without having to call register.
	 */
	public function __destruct() {
		$this->register();
	}

	/**
	 * Attempt to automatically infer the asset src from the asset loader.
	 *
	 * For example, if /folder/app.js is passed as the handle with no src,
	 * this will attempt to load the asset from the asset loader. If it
	 * is found, it will set the src to the URL of the asset and rename the handle to
	 * a sanitized version (folder-app-js).
	 */
	protected function infer_from_asset_loader() {
		// Bail if the src is set OR if the handle doesn't include an extension.
		if ( $this->src || false === strpos( $this->handle, '.' ) ) {
			return;
		}

		$asset_loader = asset_loader();

		// Bail if the asset doesn't exist.
		if ( ! $asset_loader->exists( $this->handle ) ) {
			return;
		}

		$this
			->src( $asset_loader->url( $this->handle ) )
			->dependencies(
				array_merge(
					$this->deps,
					$asset_loader->dependencies( $this->handle ),
				)
			);

		$handle = str_replace( [ '/', '.' ], '-', (string) esc_attr( $this->handle ) );

		// Ensure the handle doesn't start with a dash.
		if ( str_starts_with( $handle, '-' ) ) {
			$handle = substr( $handle, 1 );
		}

		// Update the handle with a sanitized version.
		$this->handle( $handle );
	}
}
