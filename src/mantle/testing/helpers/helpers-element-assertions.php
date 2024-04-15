<?php
/**
 * Helper for assertions against elements.
 *
 * @package Mantle
 */

namespace Mantle\Testing;

/**
 * Create a new HTML_String instance.
 *
 * @param string $html The HTML string to test.
 */
function html_string( string $html ): Assertable_HTML_String {
	return new Assertable_HTML_String( $html );
}
