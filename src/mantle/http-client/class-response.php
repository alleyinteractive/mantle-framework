<?php
/**
 * Response class file.
 *
 * @package Mantle
 */

namespace Mantle\Http_Client;

use ArrayAccess;
use InvalidArgumentException;
use LogicException;
use Mantle\Support\Collection;
use Mantle\Support\Traits\Macroable;
use SimpleXMLElement;
use WP_Error;
use WP_HTTP_Cookie;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\data_get;

/**
 * Response object from WordPress HTTP API.
 */
class Response implements ArrayAccess {
	use Macroable;

	/**
	 * Raw response from `wp_remote_request()`.
	 *
	 * @var array
	 */
	protected array $response;

	/**
	 * The decoded JSON response.
	 *
	 * @var array|null
	 */
	protected ?array $decoded;

	/**
	 * The decoded XML Element response.
	 *
	 * @var SimpleXMLElement|null
	 */
	protected ?SimpleXMLElement $element;

	/**
	 * Constructor.
	 *
	 * @param array $response Raw response from `wp_remote_request()`.
	 */
	public function __construct( array $response ) {
		$this->response = $response;

		// Format the headers to be lower-case.
		$this->response['headers'] = array_change_key_case( (array) ( $this->response['headers'] ?? [] ) );
	}

	/**
	 * Create a response object from a `wp_remote_request()` response.
	 *
	 * @throws InvalidArgumentException If the response is not an array or WP_Error.
	 * @param array|WP_Error $response Raw response from `wp_remote_request()`.
	 * @return static
	 */
	public static function create( $response ) {
		if ( is_array( $response ) ) {
			return new static( $response );
		}

		if ( $response instanceof WP_Error ) {
			return static::create_from_wp_error( $response );
		}

		throw new InvalidArgumentException( 'Unknown response type.' );
	}

	/**
	 * Create a response from a WP_Error object.
	 *
	 * @param WP_Error $error WP_Error object.
	 * @return static
	 */
	protected static function create_from_wp_error( WP_Error $error ) {
		return new static(
			[
				'body'        => $error->get_error_message(),
				'headers'     => [],
				'is_wp_error' => true,
				'response'    => [
					'code' => $error->get_error_code() ?: 500,
				],
			],
		);
	}

	/**
	 * Retrieve the raw response from `wp_remote_request()`.
	 *
	 * @return array
	 */
	public function response(): array {
		return $this->response;
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
	 * Determine if the response code was not found (404).
	 *
	 * @return bool
	 */
	public function not_found() {
		return $this->status() === 404;
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
		return $this->server_error() || $this->client_error() || $this->is_wp_error();
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
	 * Check if the error was an WP_Error.
	 *
	 * @return bool
	 */
	public function is_wp_error(): bool {
		return ! empty( $this->response['is_wp_error'] );
	}

	/**
	 * Check if the response is JSON.
	 *
	 * @return bool
	 */
	public function is_json(): bool {
		if ( false !== strpos( $this->header( 'content-type' ), 'application/json' ) ) {
			return true;
		}

		return ! empty( $this->json() );
	}

	/**
	 * Check if the response is XML. Does not validate if the response is a valid
	 * XML document.
	 *
	 * @return bool
	 */
	public function is_xml(): bool {
		if ( false !== strpos( $this->header( 'content-type' ), 'application/xml' ) ) {
			return true;
		}

		return 0 === strpos( trim( strtolower( $this->body() ) ), '<?xml' );
	}

	/**
	 * Get the raw body of the response.
	 *
	 * @return string
	 */
	public function body() {
		return (string) ( $this->response['body'] ?? '' );
	}

	/**
	 * Retrieve the file path to the downloaded file.
	 *
	 * @return string|null
	 */
	public function file(): ?string {
		return $this->response['filename'] ?? null;
	}

	/**
	 * Retrieve the file contents of the downloaded file.
	 *
	 * @return string|null
	 */
	public function file_contents(): ?string {
		return ! empty( $this->response['filename'] ) ? file_get_contents( $this->file() ) : null; // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
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
	 * Get the XML body of the response.
	 *
	 * @param string $xpath Path to pass to `SimpleXMLElement::xpath()`, optional.
	 * @param string $default Default value to return if the path does not exist.
	 * @return SimpleXMLElement Returns a specific SimpleXMLElement if path is specified, otherwise the entire document.
	 */
	public function xml( string $xpath = null, $default = null ) {
		if ( ! isset( $this->element ) ) {
			$previous = libxml_use_internal_errors( true );

			$this->element = new SimpleXMLElement( $this->body() );

			// Restore the former error level.
			libxml_use_internal_errors( $previous );
		}

		if ( ! $xpath ) {
			return $this->element;
		}

		return $this->element->xpath( $xpath ) ?: $default;
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
	 * @param mixed $offset Offset to check.
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		if ( $this->is_xml() ) {
			return isset( $this->xml()[ $offset ] );
		}

		return isset( $this->json()[ $offset ] );
	}

	/**
	 * Retrieve an attribute from the response.
	 *
	 * @param mixed $offset Offset to get.
	 * @return mixed
	 */
	public function offsetGet( mixed $offset ): mixed {
		if ( $this->is_xml() ) {
			return $this->xml()->{ $offset };
		}

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
	public function offsetSet( mixed $offset, mixed $value ): void {
		throw new LogicException( 'Response values are read-only.' );
	}

	/**
	 * Remove an attribute from the response.
	 *
	 * @throws LogicException Not supported on responses.
	 * @param mixed $offset Offset.
	 * @return void
	 */
	public function offsetUnset( mixed $offset ): void {
		throw new LogicException( 'Response values are read-only.' );
	}
}
