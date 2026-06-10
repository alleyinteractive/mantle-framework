<?php
/**
 * Request attribute class file
 *
 * @package Mantle
 */

namespace Mantle\Types\Attributes;

use Attribute;
use Mantle\Http_Client\Http_Method;
use Mantle\Support\Str;
use Mantle\Types\Validator;

/**
 * Request Attribute
 *
 * Used to define what requests a feature applies to.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class Request implements Validator {
	/**
	 * Constructor.
	 *
	 * @param string      $path   The request path to match.
	 * @param string|null $method The HTTP method to match.
	 */
	public function __construct( public readonly string $path, public readonly Http_Method|string|null $method = null ) {}

	/**
	 * Check if the request is a match for the current request.
	 */
	public function validate(): bool {
		$current_path = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! Str::is( $this->path, (string) $current_path ) ) {
			return false;
		}

		if ( $this->method ) {
			$method = strtoupper( $this->method instanceof Http_Method ? $this->method->value : $this->method );

			return strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) === $method; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		return true;
	}
}
