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
		]
	];

	/**
	 * Retrieve the generated class contents.
	 *
	 * @param string $name Class name.
	 * @return string
	 */
	public function get_generated_class( string $name ): string {
		$contents = file_get_contents( __DIR__ . '/stubs/block-class.stub' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}

	protected function get_generated_entry( string $name ): string {
		$contents = file_get_contents( __DIR__ . '/stubs/block-entry.stub' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

		return $this->replacements->replace( $contents );
	}

	protected function get_generated_edit( string $name ): string {
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

		// Register replacements for the stub files.
		$this->replacements->add( '{{ class }}', $this->get_class_name( $name ) );
		$this->replacements->add( '{{ namespace }}', $this->get_namespace( $name ) );
		$this->replacements->add( '{{ domain }}', $this->get_i18n_domain() );
		$this->replacements->add( '{{ block_name }}', $block_name );
		$this->replacements->add( '{{ block_namespace }}', $block_namespace );

		$this->generate_block_class( $name );
		$this->generate_block_entry();
		$this->generate_block_edit( $block_name );

		$this->complete_synopsis( $name );
	}

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

	protected function generate_block_entry() {

	}

	protected function generate_block_edit( string $block_name ) {
		/**
		 * Get input for:
		 * - block_category
		 * - block_description
		 * - block_icon
		 * - block_title
		 */

		$category = $this->flag( 'category', fn() => ( $this->input( 'What is a good category for this block? (empty for default)' ) ?? 'widget' ) );
		$description = $this->flag( 'description', fn() => $this->input( 'Description: ' ) );
		$icon = $this->flag( 'icon', 'generic' );
		$title = $this->flag( 'title', fn() => ( $this->input( 'Block Title: ' ) ?? $block_name ) );

		$this->replacements->add( '{{ block_category }}', $category );
		$this->replacements->add( '{{ block_description }}', $description );
		$this->replacements->add( '{{ block_icon }}', $icon );
		$this->replacements->add( '{{ block_title }}', $title );

	}
}
