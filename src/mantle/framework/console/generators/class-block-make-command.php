<?php
/**
 * Block_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Console\Command;
use Mantle\Support\Str;
use Mantle\Support\String_Replacements;
use RuntimeException;

/**
 * Block Generator
 *
 * @todo Add support for generating a controller, migration, and seed in addition to the model.
 */
class Block_Make_Command extends Generator_Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'make:block';

	/**
	 * Command Description.
	 *
	 * @var string
	 */
	protected $description = 'Generate a Gutenberg Block.';

	/**
	 * The type of class being generated.
	 *
	 * @var string
	 */
	protected $type = 'Blocks';

	/**
	 * Command signature.
	 *
	 * @var string|array
	 */
	protected $signature = 'make:block
		{name            : Full name for PHP Class (e.g. App\\Blocks\\Test_Block)}
		{block_namespace : Gutenberg Block Namespace}
		{block_name      : Gutenberg Block Name}
		{--description=  : A description for the block}
		{--category=     : A category for the block}
		{--icon=         : Icon for the block}
		{--title=        : Title for the block}';

	/**
	 * Retrieve the generated PHP class contents.
	 *
	 * @param string $name Unused.
	 * @return string
	 */
	public function get_generated_class( string $name ): string {
		$contents = file_get_contents( __DIR__ . '/stubs/block-class.stub' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}

	/**
	 * Return the generated index.js stub for this block.
	 *
	 * @return string
	 */
	protected function get_generated_entry(): string {
		$contents = file_get_contents( __DIR__ . '/stubs/block-entry.stub' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}

	/**
	 * Return the generated edit.jsx stub for this block.
	 *
	 * @return string
	 */
	protected function get_generated_edit(): string {
		$contents = file_get_contents( __DIR__ . '/stubs/block-edit.stub' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}

	/**
	 * Return the generated edit.jsx stub for this block.
	 *
	 * @return string
	 */
	protected function get_generated_view(): string {
		$contents = file_get_contents( __DIR__ . '/stubs/block-view.stub' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}

	/**
	 * Generator Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 */
	public function handle() {
		$this->replacements = new String_Replacements();

		$name            = $this->argument( 'name' );
		$block_namespace = $this->argument( 'block_namespace' );
		$block_name      = $this->argument( 'block_name' );

		/**
		 * Normalize block namespace and name to lowercase.
		 */
		$block_namespace = strtolower( $block_namespace );
		$block_name      = strtolower( $block_name );

		// Register replacements for the stub files.
		$this->replacements->add( '{{ class }}', $this->get_class_name( $name ) );
		$this->replacements->add( '{{ namespace }}', $this->get_namespace( $name ) );
		$this->replacements->add( '{{ domain }}', $this->get_i18n_domain() );
		$this->replacements->add( '{{ block_name }}', $block_name );
		$this->replacements->add( '{{ block_namespace }}', $block_namespace );

		/**
		 * Make sure the blocks folder, and new block folder, exist before moving on.
		 */
		$blocks_path = $this->get_blocks_path();
		$block_path  = $this->get_block_path( $block_name );

		if ( ! is_dir( $blocks_path ) && ! mkdir( $blocks_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $blocks_path, true );
			return Command::FAILURE;
		}

		if ( ! is_dir( $block_path ) && ! mkdir( $block_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $block_path, true );
			return Command::FAILURE;
		}

		try {
			$this->generate_block_attributes( $block_name );
			$this->generate_block_class( $name );
			$this->generate_block_entry( $block_name );
			$this->generate_block_edit( $block_name );
			$this->generate_block_view( $block_namespace, $block_name );
		} catch ( RuntimeException $e ) {
			$this->error( $e->getMessage(), true );

			return Command::FAILURE;
		}

		$this->line( '' );
		$this->success( 'Block generated successfully.' );

		return Command::SUCCESS;
	}

	/**
	 * Generate the attributes file for the block.
	 *
	 * @param string $block_name The block to generate attributes for.
	 * @return void
	 */
	protected function generate_block_attributes( string $block_name ): void {
		$entry_attributes_path = $this->get_block_path( $block_name ) . '/attributes.json';

		if ( file_exists( $entry_attributes_path ) ) {
			throw new RuntimeException( 'Block Attributes File already exists: ' . $entry_attributes_path );
		}

		// Store the generated class.
		// TODO: Dynamically retrieve attributes to add to block.
		if ( false === file_put_contents( $entry_attributes_path, json_encode( [] ) ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode, WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			throw new RuntimeException( 'Error writing to ' . $entry_attributes_path );
		}

		$this->log( 'Block Attributes File created successfully: <info>' . $entry_attributes_path . '</info>' );
	}

	/**
	 * Generate the PHP file for the Gutenberg block.
	 *
	 * @param string $name The name of the class to generate the file for.
	 * @return void
	 */
	protected function generate_block_class( string $name ): void {
		$path = $this->get_folder_path( $name );

		// Ensure the folder path exists.
		if ( ! is_dir( $path ) && ! mkdir( $path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			throw new RuntimeException( 'Error creating folder: ' . $path );
		}

		$file_path = $this->get_file_path( $name );

		if ( file_exists( $file_path ) ) {
			throw new RuntimeException( ( $this->type ?: ' File' ) . ' already exists: ' . $file_path, true );
		}

		// Store the generated class.
		if ( false === file_put_contents( $file_path, $this->get_generated_class( $name ) ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			throw new RuntimeException( 'Error writing to ' . $file_path );
		}

		$this->log( ( $this->type ?: 'File' ) . ' created successfully: <info>' . $file_path . '</info>' );
	}

	/**
	 * Generate the index.js file for the Gutenberg block.
	 *
	 * @param string $block_name The name of the block to generate the file for.
	 * @return void
	 */
	protected function generate_block_entry( string $block_name ): void {
		/**
		 * Define some block entry specific replacements. If they weren't passed as flags, we
		 * still need to get that information, so request it as input.
		 */
		$category    = Str::lower( $this->option( 'category' ) ?: $this->require_input( 'What is a good category for this block? (default: widget)', 'widget' ) );
		$description = $this->option( 'description' ) ?: $this->require_input( 'Description: ' );
		$icon        = Str::lower( $this->option( 'icon', 'generic' ) );
		$title       = $this->option( 'title' ) ?: $this->require_input( "Block Title (default: {$block_name}) ", $block_name );

		$this->replacements->add( '{{ block_category }}', $category );
		$this->replacements->add( '{{ block_description }}', $description );
		$this->replacements->add( '{{ block_icon }}', $icon );
		$this->replacements->add( '{{ block_title }}', $title );

		$entry_index_path = $this->get_block_path( $block_name ) . '/index.js';

		if ( file_exists( $entry_index_path ) ) {
			throw new RuntimeException( 'Block Index File already exists: ' . $entry_index_path );
		}

		// Store the generated class.
		if ( false === file_put_contents( $entry_index_path, $this->get_generated_entry() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			throw new RuntimeException( 'Error writing to ' . $entry_index_path );
		}

		$this->log( 'Block Index File created successfully: <info>' . $entry_index_path . '</info>' );
	}

	/**
	 * Generate the edit.jsx file for the Gutenberg block.
	 *
	 * @param string $block_name The name of the block to generate the file for.
	 * @return void
	 */
	protected function generate_block_edit( string $block_name ): void {
		$entry_edit_path = $this->get_block_path( $block_name ) . '/edit.jsx';

		if ( file_exists( $entry_edit_path ) ) {
			throw new RuntimeException( 'Block Edit File already exists: ' . $entry_edit_path );
		}

		// Store the generated class.
		if ( false === file_put_contents( $entry_edit_path, $this->get_generated_edit() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			throw new RuntimeException( 'Error writing to ' . $entry_edit_path );
		}

		$this->log( 'Block Edit File created successfully: <info>' . $entry_edit_path . '</info>' );
	}

	/**
	 * Generate the view file for the Gutenberg block.
	 *
	 * @param string $block_namespace The namespace of the block to generate the file for.
	 * @param string $block_name      The name of the block to generate the file for.
	 * @return void
	 */
	protected function generate_block_view( string $block_namespace, string $block_name ): void {
		/**
		 * Make sure the blocks views folder, and new block view folder, exist before moving on.
		 */
		$views_path = $this->get_views_path();
		$view_path  = $this->get_view_path( $block_namespace );

		if ( ! is_dir( $views_path ) && ! mkdir( $views_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			throw new RuntimeException( 'Error creating folder: ' . $views_path, true );
		}

		if ( ! is_dir( $view_path ) && ! mkdir( $view_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			throw new RuntimeException( 'Error creating folder: ' . $view_path, true );
		}

		$entry_view = $view_path . "/{$block_name}.blade.php";

		if ( file_exists( $entry_view ) ) {
			throw new RuntimeException( 'Block View already exists: ' . $entry_view );
		}

		// Store the generated class.
		if ( false === file_put_contents( $entry_view, $this->get_generated_view() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
			throw new RuntimeException( 'Error writing to ' . $entry_view );
		}

		$this->log( 'Block View created successfully: <info>' . $entry_view . '</info>' );
	}

	/**
	 * Get the base path for the generated blocks folder.
	 *
	 * @return string
	 */
	protected function get_blocks_path(): string {
		return "{$this->container->get_base_path()}/src/js/blocks/";
	}

	/**
	 * Get the base path for the genereated block.
	 *
	 * @param string $name The block name.
	 * @return string
	 */
	protected function get_block_path( string $name ): string {
		return $this->get_blocks_path() . $name;
	}

	/**
	 * Get the base path for the generated blocks folder.
	 *
	 * @return string
	 */
	protected function get_views_path(): string {
		return "{$this->container->get_base_path()}/views/blocks/";
	}

	/**
	 * Get the base path for the genereated block.
	 *
	 * @param string $namespace The block namespace.
	 * @return string
	 */
	protected function get_view_path( string $namespace ): string {
		return $this->get_views_path() . $namespace;
	}

	/**
	 * Used as a callback for a flagged value that we don't want to make required
	 * but still need to get a value for. Allows us to provide an input prompt, while
	 * also allowing for a default value.
	 *
	 * If an input is not provided, and no default value is provided, we will continue to ask
	 * until we get a response.
	 *
	 * @param string  $question The question to ask in the input.
	 * @param ?string $default The optional default value for the response.
	 * @return callable A callable to be used as a default value for the `flag` method.
	 */
	protected function require_input( string $question, ?string $default = null ): string {
		$response = $this->input( $question );

		/**
		 * If we don't get a response, recurse until we do, unless we have a defined default value.
		 */
		if ( empty( $response ) ) {
			return $default ?? ( $this->require_input( $question ) );
		}

		return $response;
	}
}
