<?php
/**
 * Ignore_Incorrect_Usage class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Set the user agent for a test method or class.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class UserAgent {
	const DESKTOP = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36';

	const MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_7_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3 Mobile/15E148 Safari/604.1';

	const TABLET = 'Mozilla/5.0 (iPad; CPU iPad OS 14_8_1 like Mac OS X) AppleWebKit/535.1 (KHTML, like Gecko) CriOS/20.0.843.0 Mobile/27W260 Safari/535.1';

	/**
	 * Constructor.
	 *
	 * @param string $ua The user agent string.
	 */
	public function __construct( public readonly string $ua ) {}
}
