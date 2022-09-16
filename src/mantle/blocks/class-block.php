<?php
/**
 * Abstract Block class
 *
 * @package Mantle
 */

namespace Mantle\Blocks;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use InvalidArgumentException;
use Mantle\Contracts\Block as Block_Contract;
use Mantle\Facade\Storage;
use Mantle\Facade\View_Loader;
use Mantle\Http\View\View;
use Mantle\Http\View\Factory as View_Factory;
use Mantle\Support\Str;

/**
 * Abstract class that handles the majority of Gutenberg Block
 * registration.
 */
abstract class Block implements Block_Contract {

	/**
	 * The block attributes.
	 *
	 * @var mixed[]
	 */
	protected array $attributes = [];

	/**
	 * A custom override value for the block's Editor script location.
	 *
	 * @var string
	 */
	protected string $editor_script = '';

	/**
	 * A custom override value for the block's Editor script asset file location.
	 *
	 * @var string
	 */
	protected string $editor_script_asset = '';

	/**
	 * A custom override value for the block's Editor script dependencies.
	 *
	 * @var array
	 */
	protected array $editor_script_dependencies = [];

	/**
	 * A custom override value for the block's Editor script handle.
	 *
	 * @var string
	 */
	protected string $editor_script_handle = '';

	/**
	 * A custom override value for the block's Editor style location.
	 *
	 * @var string
	 */
	protected string $editor_style = '';

	/**
	 * A custom override value for the block's Editor style handle.
	 *
	 * @var string
	 */
	protected string $editor_style_handle = '';

	/**
	 * The block's entry file without the extension.
	 *
	 * Generally this is going to be an index file (e.g. index.js, index.jsx)
	 *
	 * @var string
	 */
	protected string $entry_filename = 'index';

	/**
	 * A custom override value for the block's Frontend script location.
	 *
	 * @var string
	 */
	protected string $frontend_script = '';

	/**
	 * A custom override value for the block's Frontend script handle.
	 *
	 * @var string
	 */
	protected string $frontend_script_handle = '';

	/**
	 * A custom override value for the block's Frontend style location.
	 *
	 * @var string
	 */
	protected string $frontend_style = '';

	/**
	 * A custom override value for the block's Frontend style handle.
	 *
	 * @var string
	 */
	protected string $frontend_style_handle = '';

	/**
	 * Whether the block is a dynamic block or not.
	 * Default is true.
	 *
	 * @var bool
	 */
	protected bool $is_dynamic = true;

	/**
	 * The name of the block.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The namespace of the block.
	 *
	 * @var string
	 */
	protected string $namespace = '';

	/**
	 * Post types that support this block.
	 *
	 * Defaults to [ 'all' ], which is all registered post types.
	 *
	 * @var string[]
	 */
	protected array $post_types = [ 'all' ];

	/**
	 * Executed by the Block Service Provider to handle registering the block
	 * with Mantle and WordPress.
	 *
	 * @return void
	 */
	public function register(): void {

		if ( ! $this->should_register() ) {
			return;
		}

		add_action(
			'enqueue_block_editor_assets',
			function() {
				$this->register_editor_assets();
				$this->register_frontend_assets();
			}
		);

		add_action(
			'init',
			function() {
				$args = wp_parse_args(
					[
						'attributes'      => $this->get_attributes(),
						'editor_script'   => $this->get_editor_script_handle(),
						'render_callback' => $this->is_dynamic ? fn( $attributes, $content ) => $this->render( $attributes, $content ) : null,
						'editor_style'    => $this->get_editor_style_handle(),
					]
				);

				// Register the block.
				register_block_type(
					$this->get_block_name(),
					$args
				);
			}
		);
	}

	/**
	 * Whether or not a view was found for the block.
	 *
	 * @param string $name The name of the view to attempt to locate.
	 * @return bool
	 */
	protected function block_view_exists( $name ): bool {
		try {
			View_Loader::find( $name );
		} catch ( InvalidArgumentException $e ) {
			return false;
		}

		return true;
	}

	/**
	 * A helper function for formatting script handles correctly.
	 *
	 * @param string $type The type of handle being formatted.
	 * @return string
	 */
	protected function format_handle( string $type ): string {
		$name = Str::replace( '/', '-', $this->get_block_name() );
		return sprintf( '%1$s-%2$s', $name, $type );
	}

	/**
	 * Returns the blocks attributes array.
	 *
	 * @return array
	 */
	protected function get_attributes(): array {
		return $this->attributes;
	}

	/**
	 * Get the block assets object from the block assets JSON file.
	 *
	 * @return object
	 */
	protected function get_block_assets(): object {
		$root = \trailingslashit( MANTLE_BASE_DIR ) . \trailingslashit( app( 'config' )->get( 'assets.path' ) );
		$disk = Storage::create_local_driver(
			[
				'root' => $root,
			]
		);

		try {
			$assets = $disk->get( "blocks/{$this->name}/{$this->entry_filename}.asset.json" );
			$assets = \json_decode( $assets );
		} catch ( FileNotFoundException $e ) {
			return new \stdClass();
		}

		/**
		 * Fallback to an empty object if the JSON is malformed.
		 */
		if ( ! is_object( $assets ) ) {
			return new \stdClass();
		}

		return $assets;
	}

	/**
	 * Gets the block name with the proper namespace and block name.
	 * e.g. `namespace/name` if a namespace is defined, or simply
	 * `name` if a namespace is not defined.
	 *
	 * @return string
	 */
	protected function get_block_name(): string {
		return sprintf( '%1$s/%2$s', $this->get_namespace(), $this->name );
	}

	/**
	 * Get the editor scripts array of dependencies.
	 *
	 * @return array
	 */
	protected function get_editor_script_dependencies(): array {
		if (
			is_array( $this->editor_script_dependencies )
			&& ! empty( $this->editor_script_dependencies )
		) {
			return $this->editor_script_dependencies;
		}

		$assets = $this->get_block_assets();

		if ( empty( $assets->dependencies ) ) {
			return [];
		}

		return $assets->dependencies;
	}

	/**
	 * Get the version value from the Editor script's asset file.
	 *
	 * @return string
	 */
	protected function get_editor_script_version(): string {
		if (
			is_array( $this->editor_script_dependencies )
			&& ! empty( $this->editor_script_dependencies )
		) {
			return $this->editor_script_dependencies;
		}

		$assets = $this->get_block_assets();

		return $assets->version ?? \sha1( \time() );
	}

	/**
	 * Get the editor script handle. Use override if provided, otherwise generate
	 * a handle.
	 *
	 * @return string
	 */
	protected function get_editor_script_handle(): string {
		return $this->editor_script_handle ?: $this->format_handle( 'editor-script' );
	}


	/**
	 * Get the editor style handle. Use override if provided, otherwise generate
	 * a handle.
	 *
	 * @return string
	 */
	protected function get_editor_style_handle(): string {
		return $this->editor_style_handle ?: $this->format_handle( 'editor-style' );
	}

	/**
	 * Get the frontend script handle. Use override if provided, otherwise generate
	 * a handle.
	 *
	 * @return string
	 */
	protected function get_frontend_script_handle(): string {
		return $this->frontend_script_handle ?: $this->format_handle( 'frontend-script' );
	}

	/**
	 * Get the frontend style handle. Use override if provided, otherwise generate
	 * a handle.
	 *
	 * @return string
	 */
	protected function get_frontend_style_handle(): string {
		return $this->frontend_style_handle ?: $this->format_handle( 'frontend-style' );
	}

	/**
	 * Return the namespace for the block. Generate one if necessary.
	 *
	 * @return string
	 */
	protected function get_namespace(): string {
		return $this->namespace ?: Str::lower( app( 'config' )->get( 'app.namespace', 'app' ) );
	}

	/**
	 * Handle registering the Block Editor assets.
	 *
	 * @return void
	 */
	protected function register_editor_assets(): void {

		asset()
			->script(
				$this->get_editor_script_handle(),
				$this->editor_script ?: mix( "blocks/{$this->name}/index.js" )
			)
			->dependencies( $this->get_editor_script_dependencies() )
			->version( $this->get_editor_script_version() )
			->frontend( false );

		if ( ! empty( $this->editor_style ) ) {
			asset()
				->style( $this->get_editor_style_handle(), $this->editor_style )
				->frontend( false );
		}
	}

	/**
	 * Handle registering the block's frontend assets.
	 *
	 * @return void
	 */
	protected function register_frontend_assets(): void {
		if ( ! empty( $this->frontend_script ) ) {
			asset()
				->script( $this->get_frontend_script_handle(), $this->frontend_script )
				->admin( false );
		}

		if ( ! empty( $this->frontend_style ) ) {
			asset()
				->style( $this->get_frontend_style_handle(), $this->frontend_style )
				->admin( false );
		}
	}

	/**
	 * Default render function for dynamic blocks.
	 *
	 * @param array  $attributes The attributes for this block.
	 * @param string $content   The inner content for this block. Used when using InnerBlocks.
	 * @return View The content for the block.
	 */
	protected function render( array $attributes, string $content ): View|string {
		$templates = [
			$this->get_block_name(),
			"blocks/{$this->get_block_name()}",
		];

		$found = false;
		foreach ( $templates as $template ) {
			if ( $this->block_view_exists( $template ) ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return '';
		}

		/**
		 * Filter the block attributes before passing them to the template part.
		 *
		 * @param array $attributes the block attributes.
		 */
		$attributes = apply_filters( "{$this->get_block_name()}_attributes", $attributes ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		/**
		 * Execute an action before a block renders. Has access to the
		 * filtered block attributes.
		 *
		 * @param array $attributes The filtered block attributes.
		 * @param string $contents The contents of the block.
		 */
		do_action( "{$this->get_block_name()}_pre_render", $attributes, $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

		return app( View_Factory::class )->make(
			$template,
			[
				'attributes' => $attributes,
				'content'    => $content,
			]
		);
	}

	/**
	 * Helper function to determine if this block should be registered.
	 *
	 * @return boolean True if this block should be registered for the current post type, false otherwise.
	 */
	protected function should_register() {
		$should_register = in_array( 'all', $this->post_types, true ) || in_array( get_post_type(), $this->post_types, true );

		/**
		 * Allow blocks to be disabled programmatically.
		 *
		 * @param bool $should_register Whether or not the block should be registered on this post type.
		 */
		return apply_filters( "{$this->get_block_name()}_should_register", $should_register ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
	}
}
