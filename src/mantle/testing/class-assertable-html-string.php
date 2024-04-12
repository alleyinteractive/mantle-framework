<?php
/**
 * HTML_String class file
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Testing\Concerns\Element_Assertions;

/**
 * HTML String
 *
 * Perform assertions against a HTML string.
 */
class Assertable_HTML_String {
	use Element_Assertions;

	/**
	 * Constructor.
	 *
	 * @param string $content The HTML content to test.
	 */
	public function __construct( protected string $content ) {}

	/**
	 * Retrieve the content.
	 */
	protected function get_content(): string {
		return $this->content;
	}
}
