<?php

namespace Mantle\Framework\Http\Concerns;

use Mantle\Framework\Support\Str;

trait Interacts_With_Content_Types {

	/**
	 * Determine if the given content types match.
	 *
	 * @param  string $actual
	 * @param  string $type
	 * @return bool
	 */
	public static function matches_type( $actual, $type ) {
		if ( $actual === $type ) {
			return true;
		}

		$split = explode( '/', $actual );

		return isset( $split[1] ) && preg_match( '#' . preg_quote( $split[0], '#' ) . '/.+\+' . preg_quote( $split[1], '#' ) . '#', $type );
	}

	/**
	 * Determine if the request is sending JSON.
	 *
	 * @return bool
	 */
	public function is_json() {
		return Str::contains( $this->header( 'CONTENT_TYPE' ), [ '/json', '+json' ] );
	}

	/**
	 * Determine if the current request probably expects a JSON response.
	 *
	 * @return bool
	 */
	public function expects_json() {
		return ( $this->ajax() && ! $this->pjax() && $this->accepts_any_content_type() ) || $this->wants_json();
	}

	/**
	 * Determine if the current request is asking for JSON.
	 *
	 * @return bool
	 */
	public function wants_json() {
		$acceptable = $this->getAcceptableContentTypes();

		return isset( $acceptable[0] ) && Str::contains( $acceptable[0], [ '/json', '+json' ] );
	}

	/**
	 * Determines whether the current requests accepts a given content type.
	 *
	 * @param  string|array $contentTypes
	 * @return bool
	 */
	public function accepts( $contentTypes ) {
		$accepts = $this->getAcceptableContentTypes();

		if ( count( $accepts ) === 0 ) {
			return true;
		}

		$types = (array) $contentTypes;

		foreach ( $accepts as $accept ) {
			if ( $accept === '*/*' || $accept === '*' ) {
				return true;
			}

			foreach ( $types as $type ) {
				if ( $this->matches_type( $accept, $type ) || $accept === strtok( $type, '/' ) . '/*' ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Return the most suitable content type from the given array based on content negotiation.
	 *
	 * @param  string|array $contentTypes
	 * @return string|null
	 */
	public function prefers( $contentTypes ) {
		$accepts = $this->getAcceptableContentTypes();

		$contentTypes = (array) $contentTypes;

		foreach ( $accepts as $accept ) {
			if ( in_array( $accept, [ '*/*', '*' ] ) ) {
				return $contentTypes[0];
			}

			foreach ( $contentTypes as $contentType ) {
				$type = $contentType;

				if ( ! is_null( $mimeType = $this->getMimeType( $contentType ) ) ) {
					$type = $mimeType;
				}

				if ( $this->matches_type( $type, $accept ) || $accept === strtok( $type, '/' ) . '/*' ) {
					return $contentType;
				}
			}
		}
	}

	/**
	 * Determine if the current request accepts any content type.
	 *
	 * @return bool
	 */
	public function accepts_any_content_type() {
		$acceptable = $this->getAcceptableContentTypes();

		return count( $acceptable ) === 0 || (
			isset( $acceptable[0] ) && ( $acceptable[0] === '*/*' || $acceptable[0] === '*' )
		);
	}

	/**
	 * Determines whether a request accepts JSON.
	 *
	 * @return bool
	 */
	public function accepts_json() {
		return $this->accepts( 'application/json' );
	}

	/**
	 * Determines whether a request accepts HTML.
	 *
	 * @return bool
	 */
	public function accepts_html() {
		return $this->accepts( 'text/html' );
	}

	/**
	 * Get the data format expected in the response.
	 *
	 * @param  string $default
	 * @return string
	 */
	public function format( $default = 'html' ) {
		foreach ( $this->getAcceptableContentTypes() as $type ) {
			if ( $format = $this->getFormat( $type ) ) {
				return $format;
			}
		}

		return $default;
	}
}
