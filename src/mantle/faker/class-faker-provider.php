<?php
/**
 * Faker_Provider class file.
 *
 * @package Mantle
 */

namespace Mantle\Faker;

use Faker\Provider\Base;
use Faker\Provider\Lorem;

use function Mantle\Support\Helpers\collect;

/**
 * Faker Block Provider
 */
class Faker_Provider extends Base {
	/**
	 * Compile a set of blocks.
	 *
	 * @param string[] $blocks Blocks to compile.
	 */
	public static function blocks( array $blocks ): string {
		return implode( "\n\n", $blocks );
	}

	/**
	 * Build a heading block.
	 *
	 * @param string|int|null $text Heading text.
	 * @param int             $level Heading level.
	 */
	public static function heading_block( string|null|int $text = null, int $level = 2 ): string {
		if ( is_int( $text ) ) {
			$level = $text;
			$text  = null;
		}

		return static::block(
			'heading',
			sprintf( '<h%d>%s</h%d>', $level, $text ?: Lorem::sentence(), $level ),
			[
				'level' => $level,
			],
		);
	}

	/**
	 * Build a paragraph block.
	 *
	 * @param string|int|null $text Text for the block.
	 * @param int             $sentences Number of sentences in the block.
	 */
	public static function paragraph_block( string|null|int $text = null, int $sentences = 3 ): string {
		if ( is_int( $text ) ) {
			$sentences = $text;
			$text      = null;
		}

		return static::block(
			'paragraph',
			sprintf( '<p>%s</p>', $text ?: Lorem::sentences( $sentences, true ) )
		);
	}

	/**
	 * Generate a set of paragraph blocks.
	 *
	 * @param int  $count Number of paragraph blocks to generate.
	 * @param bool $as_text Return as text or an array of blocks.
	 * @return string|string[]
	 * @phpstan-return ($as_text is true ? string : array<string>)
	 */
	public static function paragraph_blocks( int $count = 3, bool $as_text = true ): string|array {
		$paragraphs = [];
		for ( $i = 0; $i < $count; $i++ ) {
			$paragraphs[] = static::paragraph_block();
		}

		return $as_text ? implode( "\n\n", $paragraphs ) : $paragraphs;
	}

	/**
	 * Build an image block.
	 *
	 * @param string|null  $url Image URL.
	 * @param string|null  $alt Image alt text.
	 * @param array<mixed> $attributes Additional attributes for the block.
	 */
	public static function image_block( ?string $url = null, ?string $alt = null, array $attributes = [] ): string {
		$image = sprintf(
			'<figure class="wp-block-image"><img src="%s"%s/></figure>',
			$url ?? 'https://picsum.photos/' . wp_rand( 100, 1000 ) . '/' . wp_rand( 100, 1000 ),
			$alt ? ' alt="' . esc_attr( $alt ) . '"' : '',
		);

		return static::block( 'image', $image, $attributes );
	}

	/**
	 * Generate a list block.
	 *
	 * @param string[]             $items List items.
	 * @param bool                 $ordered Whether the list is ordered or unordered.
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	public static function list_block( array $items = [], bool $ordered = false, array $attributes = [] ): string {
		$list_type  = $ordered ? 'ol' : 'ul';
		$list_items = collect( $items )->map(
			static fn ( string $item ) => static::block(
				name: 'list-item',
				content: "<li>{$item}</li>",
			),
		)->implode( "\n" );

		if ( $ordered ) {
			$attributes['ordered'] = true;
		}

		return static::block(
			attributes: $attributes,
			name: 'list',
			content: sprintf( "<%s class=\"wp-block-list\">\n%s\n</%s>", $list_type, $list_items, $list_type ),
		);
	}

	/**
	 * Build a reusable block.
	 *
	 * @param int $id Block ID.
	 */
	public static function reusable_block( int $id ): string {
		return static::block( 'block', null, [ 'ref' => $id ] );
	}

	/**
	 * Build a button block (or rather a buttons block with a button inside).
	 *
	 * @param string       $text Button text.
	 * @param string       $url Button URL.
	 * @param array<mixed> $attributes Additional attributes for the block.
	 */
	public static function button_block( string $text, string $url, array $attributes = [] ): string {
		return static::block(
			name: 'buttons',
			content: '<div class="wp-block-buttons">' . static::block(
				name: 'button',
				content: '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a></div>',
			) . '</div>',
		);
	}

	/**
	 * Build a block for Gutenberg.
	 *
	 * @param string       $name Block name.
	 * @param string|null  $content Content for the block.
	 * @param array<mixed> $attributes Attributes for the block.
	 */
	public static function block( string $name, ?string $content = null, array $attributes = [] ): string {
		// Add a newline before and after the content.
		if ( ! is_null( $content ) ) {
			$content = "\n{$content}\n";
		}

		return get_comment_delimited_block_content( $name, $attributes, $content ?? '' );
	}
}
