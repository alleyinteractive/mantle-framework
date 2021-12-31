<?php
/**
 * Response class file.
 *
 * @package Mantle
 */

namespace Mantle\Http\Client;

use ArrayAccess;
use LogicException;
use Mantle\Support\Collection;
use WP_HTTP_Cookie;

use function Mantle\Framework\Helpers\collect;
use function Mantle\Framework\Helpers\data_get;

/**
 * Response object from WordPress HTTP API.
 */
class Response implements ArrayAccess {
	/**
	 * Raw response from `wp_remote_request()`.
	 *
	 * @var array
	 */
	protected array $response;

	/**
	 * The decoded JSON response.
	 *
	 * @var array
	 */
	protected array $decoded;

	/**
	 * Constructor.
	 *
	 * @param array $response Raw response from `wp_remote_request()`.
	 */
	public function __construct( array $response ) {
		$this->response = $response;

		// Format the headers to be lower-case.
		$this->response['headers'] = array_change_key_case( $this->response['headers'] ?? [] );
	}

	/**
	 * Retrieve all the headers from a response.
	 *
	 * @return array
	 */
	public function headers(): array {
		return (array) ( $this->response['headers'] ?? [] );
	}

	/**
	 * Retrieve a specific header (headers are case-insensitive).
	 *
	 * @param string $header Header to retrieve.
	 * @return mixed
	 */
	public function header( string $header ) {
		$header = strtolower( $header );
		return $this->headers()[ $header ] ?? null;
	}

	/**
	 * Retrieve the status code for the response.
	 *
	 * @return int
	 */
	public function status(): int {
		return (int) ( $this->response['response']['code'] ?? 0 );
	}

	/**
	 * Determine if the request was successful.
	 *
	 * @return bool
	 */
	public function successful() {
		return $this->status() >= 200 && $this->status() < 300;
	}

	/**
	 * Determine if the response code was "OK".
	 *
	 * @return bool
	 */
	public function ok() {
		return $this->status() === 200;
	}

	/**
	 * Determine if the response was a redirect.
	 *
	 * @return bool
	 */
	public function redirect() {
		return $this->status() >= 300 && $this->status() < 400;
	}

	/**
	 * Determine if the response was a 401 "Unauthorized" response.
	 *
	 * @return bool
	 */
	public function unauthorized(): bool {
		return $this->status() === 401;
	}

	/**
	 * Determine if the response was a 403 "Forbidden" response.
	 *
	 * @return bool
	 */
	public function forbidden(): bool {
		return $this->status() === 403;
	}

	/**
	 * Determine if the response indicates a client or server error occurred.
	 *
	 * @return bool
	 */
	public function failed(): bool {
			return $this->server_error() || $this->client_error();
	}

	/**
	 * Determine if the response indicates a client error occurred.
	 *
	 * @return bool
	 */
	public function client_error(): bool {
		return $this->status() >= 400 && $this->status() < 500;
	}

	/**
	 * Determine if the response indicates a server error occurred.
	 *
	 * @return bool
	 */
	public function server_error(): bool {
		return $this->status() >= 500;
	}

	/**
	 * Get the body of the response.
	 *
	 * @return string
	 */
	public function body() {
		return (string) ( $this->response['body'] ?? '' );
	}

	/**
	 * Get the JSON decoded body of the response as an array or scalar value.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed
	 */
	public function json( $key = null, $default = null ) {
		if ( ! isset( $this->decoded ) ) {
			$this->decoded = json_decode( $this->body(), true );
		}

		if ( is_null( $key ) ) {
			return $this->decoded;
		}

		return data_get( $this->decoded, $key, $default );
	}

	/**
	 * Get the JSON decoded body of the response as an object.
	 *
	 * @return object
	 */
	public function object() {
		return json_decode( $this->body(), false );
	}

	/**
	 * Get the JSON decoded body of the response as a collection.
	 *
	 * @param  string|null $key
	 * @return Collection
	 */
	public function collect( $key = null ) {
		return Collection::make( $this->json( $key ) );
	}

	/**
	 * Retrieve the cookies from the response.
	 *
	 * @return WP_HTTP_Cookie[]
	 */
	public function cookies(): array {
		return $this->response['cookies'] ?? [];
	}

	/**
	 * Retrieve a specific cookie by name.
	 *
	 * @param string $name Cookie name.
	 * @return WP_HTTP_Cookie
	 */
	public function cookie( string $name ): ?WP_HTTP_Cookie {
		return collect( $this->cookies() )
			->key_by( 'name' )
			->get( $name );
	}

	/**
	 * Dump the response to the screen.
	 *
	 * @return static
	 */
	public function dump() {
		dump( $this->response );
		return $this;
	}

	/**
	 * Dump the response to the screen and exit.
	 *
	 * @return void
	 */
	public function dd() {
		$this->dump();
		exit( 1 );
	}

	/**
	 * Check if an attribute exists on the response.
	 *
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->json()[ $offset ] );
	}

	/**
	 * Retrieve an attribute from the response.
	 *
	 * @param mixed $offset Offset to get.
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->json()[ $offset ];
	}

	/**
	 * Set an attribute on the response.
	 *
	 * @throws LogicException Not supported on responses.
	 *
	 * @param mixed $offset Offset.
	 * @param mixed $value Value.
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		throw new LogicException( 'Response values are read-only.' );
	}

	/**
	 * Remove an attribute from the response.
	 *
	 * @throws LogicException Not supported on responses.
	 * @param mixed $offset Offset.
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		throw new LogicException( 'Response values are read-only.' );
	}
}
