<?php
/**
 * Pooled_Pending_Request class file
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

/**
 * Pooled Pending Request
 */
class Pooled_Pending_Request extends Pending_Request {
	/**
	 * Create a new Pooled_Pending_Request from a Pending_Request.
	 *
	 * @param Pending_Request $request The pending request to convert.
	 */
	public static function from_pending_request( Pending_Request $request ): self {
		$pooled_request = new self();

		if ( $base_url = $request->get_base_url() ) {
			$pooled_request->set_base_url( $base_url );
		}

		if ( $url = $request->get_url() ) {
			$pooled_request->set_url( $url );
		}

		$pooled_request->set_method( $request->get_method() );

		$pooled_request->options = $request->options;

		return $pooled_request;
	}

	/**
	 * Issue a GET request to the given URL.
	 *
	 * @param  string                           $url URL to retrieve.
	 * @param  array<string, mixed>|string|null $query Query parameters (assumed to be urlencoded).
	 */
	public function get( string $url, array|string|null $query = null ): static {
		$this->set_method( Http_Method::GET )->set_url( $url );

		if ( $query ) {
			$this->options['query'] = $query;
		}

		return $this;
	}

	/**
	 * Issue a HEAD request to the given URL.
	 *
	 * @param  string                           $url URL to retrieve.
	 * @param  array<string, mixed>|string|null $query Query parameters (assumed to be urlencoded).
	 */
	public function head( string $url, array|string|null $query = null ): static {
		$this->set_method( Http_Method::HEAD )->set_url( $url );

		if ( $query ) {
			$this->options['query'] = $query;
		}

		return $this;
	}

	/**
	 * Issue a POST request to the given URL.
	 *
	 * @param  string                    $url URL to post.
	 * @param  array<string, mixed>|null $data Data to send with the request.
	 */
	public function post( string $url, ?array $data = null ): static {
		$this->set_method( Http_Method::POST )->set_url( $url );

		if ( $data ) {
			$this->options[ $this->body_format ] = $data;
		}

		return $this;
	}

	/**
	 * Issue a PATCH request to the given URL.
	 *
	 * @param  string                    $url URL to patch.
	 * @param  array<string, mixed>|null $data Data to send with the request.
	 */
	public function patch( string $url, ?array $data = null ): static {
		$this->set_method( Http_Method::PATCH )->set_url( $url );

		if ( $data ) {
			$this->options[ $this->body_format ] = $data;
		}

		return $this;
	}

	/**
	 * Issue a PUT request to the given URL.
	 *
	 * @param  string                    $url URL to put.
	 * @param  array<string, mixed>|null $data Data to send with the request.
	 */
	public function put( string $url, ?array $data = null ): static {
		$this->set_method( Http_Method::PUT )->set_url( $url );

		if ( $data ) {
			$this->options[ $this->body_format ] = $data;
		}

		return $this;
	}

	/**
	 * Issue a DELETE request to the given URL.
	 *
	 * @param  string                    $url URL to delete.
	 * @param  array<string, mixed>|null $data Data to send with the request.
	 */
	public function delete( string $url, ?array $data = null ): static {
		$this->set_method( Http_Method::DELETE )->set_url( $url );

		if ( $data ) {
			$this->options[ $this->body_format ] = $data;
		}

		return $this;
	}
}
