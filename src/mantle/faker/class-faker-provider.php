<?php
/**
 * Faker_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Faker;

use Faker\Provider\Base;
use Faker\Provider\Lorem;

/**
 * Faker Block Provider
 */
class Faker_Provider extends Base {
	/**
	 * Build a paragraph block.
	 *
	 * @param int $sentences Number of sentences in the block.
	 * @return string
	 */
	public static function paragraph_block( int $sentences = 3 ): string {
		return static::block(
			'paragraph',
			sprintf( '<p>%s</p>', Lorem::sentences( $sentences, true ) )
		);
	}

	/**
	 * Generate a set of paragraph blocks.
	 *
	 * @param int  $count Number of paragraph blocks to generate.
	 * @param bool $as_text Return as text or an array of blocks.
	 * @return string|array
	 */
	public function paragraph_blocks( int $count = 3, bool $as_text = true ) {
		$paragraphs = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$paragraphs[] = static::paragraph_block();
		}

		return $as_text ? implode( "\n\n", $paragraphs ) : $paragraphs;
	}

	/**
	 * Build a block for Gutenberg.
	 *
	 * @param string $block_name Block name.
	 * @param string $content Content for the block.
	 * @param array  $attributes Attributes for the block.
	 * @return string
	 */
	public static function block( string $block_name, string $content = '', array $attributes = [] ): string {
		return get_comment_delimited_block_content( $block_name, $attributes, $content );
	}
}
