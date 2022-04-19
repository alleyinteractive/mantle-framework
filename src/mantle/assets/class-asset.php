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
	 * Asset type (script/style).
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Asset handle.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * Asset URL.
	 *
	 * @var string|null
	 */
	protected $src = null;

	/**
	 * Asset dependencies.
	 *
	 * @var string[]
	 */
	protected $deps = [];

	/**
	 * Condition to load the asset.
	 *
	 * @var string
	 */
	protected $condition = 'global';

	/**
	 * Load method for the asset (async/sync/async-defer).
	 *
	 * @var string
	 */
	protected $load_method = Load_Method::SYNC;

	/**
	 * Load hook for the asset.
	 *
	 * @var string
	 */
	protected $load_hook = Load_Hook::HEADER;

	/**
	 * Version for the asset.
	 *
	 * @var string|null
	 */
	protected $version = null;

	/**
	 * Asset style media.
	 *
	 * @var string
	 */
	protected $media = null;

	/**
	 * Constructor.
	 *
	 * @param string          $type     Asset type (script/style).
	 * @param string          $handle Asset handle.
	 * @param string          $src Script URL.
	 * @param string[]|string $deps Script dependencies.
	 * @param array|string    $condition Condition to load.
	 * @param string          $load_method Load method.
	 * @param string          $load_hook Load hook.
	 * @param string|null     $version Script version.
	 */
	public function __construct(
		string $type,
		string $handle,
		?string $src = null,
		array $deps = [],
		$condition = 'global',
		string $load_method = Load_Method::SYNC,
		string $load_hook = Load_Hook::HEADER,
		?string $version = null
	) {
		$this->type        = $type;
		$this->handle      = $handle;
		$this->src         = $src;
		$this->deps        = $deps;
		$this->condition   = $condition;
		$this->load_method = $load_method;
		$this->load_hook   = $load_hook;
		$this->version     = $version;
	}

	/**
	 * Set an asset to load with async.
	 *
	 * @return static
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
	 *
	 * @return static
	 */
	public function sync(): Asset {
		$this->load_method = Load_Method::SYNC;
		return $this;
	}

	/**
	 * Defer a script.
	 *
	 * @return static
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
	 * Load the asset in the header.
	 *
	 * @return Asset
	 */
	public function header(): Asset {
		$this->load_hook = Load_Hook::HEADER;
		return $this;
	}

	/**
	 * Load the asset in the footer.
	 *
	 * @return Asset
	 */
	public function footer(): Asset {
		$this->load_hook = Load_Hook::FOOTER;
		return $this;
	}

	/**
	 * Load the asset on a specific hook.
	 *
	 * @param string $hook Hook to load on.
	 * @return Asset
	 */
	public function hook( string $hook ): Asset {
		$this->load_hook = $hook;
		return $this;
	}

	/**
	 * Set the version of the asset.
	 *
	 * @param string|null $version Version to set.
	 * @return Asset
	 */
	public function version( ?string $version ): Asset {
		$this->version = $version;
		return $this;
	}

	/**
	 * Set the asset dependencies.
	 *
	 * @param string[] $dependencies Dependencies to set.
	 * @return Asset
	 */
	public function dependencies( array $dependencies ): Asset {
		$this->deps = $dependencies;
		return $this;
	}

	/**
	 * Add a dependency to the asset.
	 *
	 * @param string $dependency Dependency to add.
	 * @return Asset
	 */
	public function add_dependency( string $dependency ): Asset {
		$this->deps[] = $dependency;
		return $this;
	}

	/**
	 * Set the asset handle.
	 *
	 * @param string $handle Handle to set.
	 * @return Asset
	 */
	public function handle( string $handle ): Asset {
		$this->handle = $handle;
		return $this;
	}

	/**
	 * Set the asset URL.
	 *
	 * @param string $src URL to set.
	 * @return Asset
	 */
	public function src( string $src = '' ): Asset {
		$this->src = $src;
		return $this;
	}

	/**
	 * Set the media to use for style assets.
	 *
	 * @param string $media Media to set.
	 * @return Asset
	 */
	public function media( string $media = '' ): Asset {
		$this->media = $media;
		return $this;
	}

	/**
	 * Register the asset with asset manager. If called before
	 * 'wp_enqueue_scripts', it will defer registration until then.
	 */
	public function register(): void {
		hook_callable( 'wp_enqueue_scripts', fn () => $this->register_asset() );
		hook_callable( 'admin_enqueue_scripts', fn () => $this->register_asset() );
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
}
