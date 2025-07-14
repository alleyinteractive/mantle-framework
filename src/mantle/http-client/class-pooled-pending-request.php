<?php
namespace Mantle\Http_Client;

class Pooled_Pending_Request extends Pending_Request {
	/**
	 * Constructor.
	 *
	 * @param Pool $pool The pool this request belongs to.
	 */
	// public function __construct( protected Pool $pool ) {}

	/**
	 * Make a GET request to the given URL with optional query parameters.
	 *
	 * @param string $url The URL to send the GET request to.
	 * @param array<string, mixed>|string|null $query Optional query parameters to include in the request.
	 */
	public function get( string $url, array|string|null $query = null ): static {
		$this->url( $url )->method( Http_Method::GET );

		if ( ! is_null( $query ) ) {
			$this->with_options( [
				'query' => $query,
			] );
		}

		// $this->pool[] = $this;

		return $this;
	}

	/**
	 * Make a HEAD request to the given URL with optional query parameters.
	 *
	 * @param string $url The URL to send the HEAD request to.
	 * @param array<string, mixed>|string|null $query Optional query parameters to include in the request.
	 */
	public function head( string $url, array|string|null $query = null ): static {
		$this->url( $url )->method( Http_Method::HEAD );

		if ( ! is_null( $query ) ) {
			$this->with_options( [
				'query' => $query,
			] );
		}

		// $this->pool[] = $this;

		return $this;
	}

	/**
	 * Make a POST request to the given URL with optional body data.
	 *
	 * @param string $url The URL to send the POST request to.
	 * @param array<string, mixed>|null $data Optional body data to include in the request.
	 */
	public function post( string $url, ?array $data = null ): static {
		$this->url( $url )->method( Http_Method::POST );

		if ( ! is_null( $data ) ) {
			$this->with_options( [
				$this->body_format => $data,
			] );
		}

		// $this->pool[] = $this;

		return $this;
	}

	/**
	 * Make a PATCH request to the given URL with optional body data.
	 *
	 * @param string $url The URL to send the PATCH request to.
	 * @param array<string, mixed>|null $data Optional body data to include in the request.
	 */
	public function patch( string $url, ?array $data = null ): static {
		$this->url( $url )->method( Http_Method::PATCH );

		if ( ! is_null( $data ) ) {
			$this->with_options( [
				$this->body_format => $data,
			] );
		}

		$this->pool[] = $this;

		return $this;
	}

	/**
	 * Make a PUT request to the given URL with optional body data.
	 *
	 * @param string $url The URL to send the PUT request to.
	 * @param array<string, mixed>|null $data Optional body data to include in the request.
	 */
	public function put( string $url, ?array $data = null ): static {
		$this->url( $url )->method( Http_Method::PUT );

		if ( ! is_null( $data ) ) {
			$this->with_options( [
				$this->body_format => $data,
			] );
		}

		$this->pool[] = $this;

		return $this;
	}

	/**
	 * Make a DELETE request to the given URL with optional body data.
	 *
	 * @param string $url The URL to send the DELETE request to.
	 * @param array<string, mixed>|null $data Optional body data to include in the request.
	 */
	public function delete( string $url, ?array $data = null ): static {
		$this->url( $url )->method( Http_Method::DELETE );

		if ( ! is_null( $data ) ) {
			$this->with_options( [
				$this->body_format => $data,
			] );
		}

		$this->pool[] = $this;

		return $this;
	}

	/**
	 * Send the request and return the response.
	 *
	 * This method is overridden to ensure that the request is sent
	 * as part of the pool's request handling.
	 */
	public function send( string|Http_Method|null $method = null, ?string $url = null, array $options = [] ): Response {
		dd('send');
		return new Response( [] );
	}
}
