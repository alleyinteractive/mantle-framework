<?php
/**
 * Block_Make_Command class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Console\Generators;

use Mantle\Support\Str;

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
	 * Command synopsis.
	 *
	 * @var string|array
	 */
	protected $synopsis = [
		[
			'description' => 'Full name for PHP Class (e.g. App\\Blocks\\Test_Block)',
			'name'        => 'name',
			'optional'    => false,
			'type'        => 'positional',
		],
		[
			'description' => 'Gutenberg Block Namespace',
			'name'        => 'block_namespace',
			'optional'    => false,
			'type'        => 'positional',
		],
		[
			'description' => 'Gutenberg Block Name',
			'name'        => 'block_name',
			'optional'    => false,
			'type'        => 'positional',
		],
		[
			'description' => 'A description for the block',
			'name'        => 'description',
			'optional'    => true,
			'type'        => 'flag',
		],
		[
			'description' => 'A category for the block',
			'name'        => 'category',
			'optional'    => true,
			'type'        => 'flag',
		],
		[
			'description' => 'A dashicon name for the block',
			'name'        => 'icon',
			'optional'    => true,
			'type'        => 'flag',
		],
	];

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
	 * Generator Command.
	 *
	 * @todo Replace with a filesystem abstraction.
	 *
	 * @param array $args Command Arguments.
	 * @param array $assoc_args Command flags.
	 */
	public function handle( array $args, array $assoc_args = [] ) {

		if ( empty( $args[0] ) ) {
			$this->error( 'Missing block class name.', true );
		}

		if ( empty( $args[1] ) ) {
			$this->error( 'Missing block namespace.', true );
		}

		if ( empty( $args[2] ) ) {
			$this->error( 'Missing block name.', true );
		}

		list( $name, $block_namespace, $block_name ) = $args;

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
		}

		if ( ! is_dir( $block_path ) && ! mkdir( $block_path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $block_path, true );
		}

		$this->generate_block_attributes( $block_name );
		$this->generate_block_class( $name );
		$this->generate_block_entry( $block_name );
		$this->generate_block_edit( $block_name );

		$this->complete_synopsis( $name );
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
			$this->error( 'Block Attributes File already exists: ' . $entry_attributes_path );
			return;
		}

		// Store the generated class.
		try {
			// TODO: Dynamically retrieve attributes to add to block.
			if ( false === file_put_contents( $entry_attributes_path, \json_encode( [] ) ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				$this->error( 'Error writing to ' . $entry_attributes_path );
			}
		} catch ( \Throwable $e ) {
			dump( $e );
			$this->error( 'There was an error generating: ' . $e->getMessage(), true );
		}

		$this->log( 'Block Attributes File created successfully: ' . $entry_attributes_path );
	}

	/**
	 * Generate the PHP file for the Gutenberg block.
	 *
	 * @param string $name The name of the class to generate the file for.
	 * @return void
	 */
	protected function generate_block_class( string $name ) {
		$path = $this->get_folder_path( $name );

		// Ensure the folder path exists.
		if ( ! is_dir( $path ) && ! mkdir( $path, 0700, true ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.directory_mkdir
			$this->error( 'Error creating folder: ' . $path );
		}

		$file_path = $this->get_file_path( $name );

		if ( file_exists( $file_path ) ) {
			$this->error( ( $this->type ?: ' File' ) . ' already exists: ' . $file_path, true );
		}

		// Store the generated class.
		try {
			if ( false === file_put_contents( $file_path, $this->get_generated_class( $name ) ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				$this->error( 'Error writing to ' . $file_path );
			}
		} catch ( \Throwable $e ) {
			dump( $e );
			$this->error( 'There was an error generating: ' . $e->getMessage(), true );
		}

		$this->log( ( $this->type ?: 'File' ) . ' created successfully: ' . $file_path );
	}

	/**
	 * Generate the index.js file for the Gutenberg block.
	 *
	 * @param string $block_name The name of the block to generate the file for.
	 * @return void
	 */
	protected function generate_block_entry( string $block_name ) {
		/**
		 * Define some block entry specific replacements. If they weren't passed as flags, we
		 * still need to get that information, so request it as input.
		 */
		$category    = Str::lower( $this->flag( 'category' ) ?: $this->require_input( 'What is a good category for this block? (default: widget)', 'widget' ) );
		$description = $this->flag( 'description' ) ?: $this->require_input( 'Description: ' );
		$icon        = Str::lower( $this->flag( 'icon', 'generic' ) );
		$title       = $this->flag( 'title' ) ?: $this->require_input( "Block Title (default: {$block_name}) ", $block_name );

		$this->replacements->add( '{{ block_category }}', $category );
		$this->replacements->add( '{{ block_description }}', $description );
		$this->replacements->add( '{{ block_icon }}', $icon );
		$this->replacements->add( '{{ block_title }}', $title );

		$entry_index_path = $this->get_block_path( $block_name ) . '/index.js';

		if ( file_exists( $entry_index_path ) ) {
			$this->error( 'Block Index File already exists: ' . $entry_index_path );
			return;
		}

		// Store the generated class.
		try {
			if ( false === file_put_contents( $entry_index_path, $this->get_generated_entry() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				$this->error( 'Error writing to ' . $entry_index_path );
			}
		} catch ( \Throwable $e ) {
			dump( $e );
			$this->error( 'There was an error generating: ' . $e->getMessage(), true );
		}

		$this->log( 'Block Index File created successfully: ' . $entry_index_path );
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
			$this->error( 'Block Edit File already exists: ' . $entry_edit_path );
			return;
		}

		// Store the generated class.
		try {
			if ( false === file_put_contents( $entry_edit_path, $this->get_generated_edit() ) ) { // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
				$this->error( 'Error writing to ' . $entry_edit_path );
			}
		} catch ( \Throwable $e ) {
			dump( $e );
			$this->error( 'There was an error generating: ' . $e->getMessage(), true );
		}

		$this->log( 'Block Edit File created successfully: ' . $entry_edit_path );
	}

	/**
	 * Get the base path for the generated blocks folder.
	 *
	 * @return string
	 */
	protected function get_blocks_path(): string {
		return "{$this->app->get_base_path()}/src/js/blocks/";
	}

	/**
	 * Get the base path for the genereated block.
	 *
	 * @param string $name The block name.
	 * @return string
	 */
	protected function get_block_path( string $name ): string {
		return $this->get_blocks_path() . '/' . $name;
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
