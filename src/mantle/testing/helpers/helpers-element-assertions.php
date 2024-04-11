<?php
/**
 * Helper for assertions against a HTML string.
 */

namespace Mantle\Testing;

/**
 * Create a new HTML_String instance.
 *
 * @param string $html The HTML string to test.
 * @return HTML_String
 */
function html_string( string $html ): HTML_String {
	return new HTML_String( $html );
}
