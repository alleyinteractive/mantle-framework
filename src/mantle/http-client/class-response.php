<?php
/**
 * Response class file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Http_Client;

use ArrayAccess;
use LogicException;
use Mantle\Support\Collection;
use Mantle\Support\HTML;
use Mantle\Support\Mixed_Data;
use Mantle\Support\Traits\Macroable;
use Mantle\Testing\Assertable_Json_String;
use SimpleXMLElement;
use WP_Error;
use WP_Http_Cookie;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\data_get;

/**
 * Response object from WordPress HTTP API.
 *
 * @todo Add assertions to the responses.
 *
 * @phpstan-type CoreResponse array{
 *   body?: string,
 *   cookies?: \WP_Http_Cookie[],
 *   filename?: string|null,
 *   headers?: \WpOrg\Requests\Utility\CaseInsensitiveDictionary,
 *   response?: array{
 *     code: int,
 *     message: string,
 *   },
 * }
 *
 * @phpstan-type WpHttpRequestResponse array{
 *   body?: string,
 *   cookies?: \WP_Http_Cookie[],
 *   filename?: string|null,
 *   headers?: array<string, string>,
 *   is_wp_error?: bool,
 *   response?: array{
 *     code: int,
 *     message: string,
 *   },
 *   http_response?: \WP_HTTP_Requests_Response,
 * }
 */
class Response implements ArrayAccess {
	use Concerns\Interacts_With_Feeds;
	use Macroable;

	/**
	 * The decoded JSON response.
	 *
	 * @var array<string, mixed>|null
	 */
	protected ?array $decoded = null;

	/**
	 * The decoded XML Element response.
	 */
	protected ?SimpleXMLElement $element = null;

	/**
	 * Processed response from `wp_remote_request()`.
	 *
	 * @var WpHttpRequestResponse
	 */
	protected array $response;

	/**
	 * The request URL.
	 */
	protected ?string $url = null;

	/**
	 * Determine if the response was created from the cache.
	 *
	 * This property uses a 'mixed' status to prevent a fatal error when upgrading
	 * projects. In the future, this should be changed to use the Cache_Status
	 * enum or false.
	 *
	 * @param Cache_Status|false $cached Cache status of the response.
	 */
	public mixed $cached = false;

	/**
	 * Constructor.
	 *
	 * @param CoreResponse|WpHttpRequestResponse $response Raw response from `wp_remote_request()`.
	 */
	public function __construct( array $response ) {
		// Serialize the headers from a CaseInsensitiveDictionary to an array.
		if ( isset( $response['headers'] ) && $response['headers'] instanceof CaseInsensitiveDictionary ) {
			$response['headers'] = $response['headers']->getAll();
		}

		// Format the headers to be lower-case.
		$response['headers'] = array_change_key_case( (array) ( $response['headers'] ?? [] ) );

		$this->response = $response;

		// @phpstan-ignore instanceof.alwaysTrue
		if ( isset( $response['http_response'] ) && $response['http_response'] instanceof \WP_HTTP_Requests_Response ) {
			$this->url = $response['http_response']->get_response_object()->url;
		} else {
			$this->url = null;
		}
	}

	/**
	 * Create a response object from a `wp_remote_request()` response.
	 *
	 * @param CoreResponse|WpHttpRequestResponse|WP_Error $response Raw response from `wp_remote_request()`.
	 */
	public static function create( array|WP_Error $response ): static {
		if ( $response instanceof WP_Error ) {
			return static::create_from_wp_error( $response );
		}

		return new static( $response );
	}

	/**
	 * Create a response from a WP_Error object.
	 *
	 * @param WP_Error $error WP_Error object.
	 */
	protected static function create_from_wp_error( WP_Error $error ): static {
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
	 * @return WpHttpRequestResponse
	 */
	public function response(): array {
		return $this->response;
	}

	/**
	 * Retrieve all the headers from a response.
	 *
	 * @return array<string, string> Headers from the response.
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
	 */
	public function status(): int {
		return (int) ( $this->response['response']['code'] ?? 0 );
	}

	/**
	 * Determine if the request was successful.
	 */
	public function successful(): bool {
		return $this->status() >= 200 && $this->status() < 300;
	}

	/**
	 * Determine if the response code was "OK".
	 */
	public function ok(): bool {
		return $this->status() === 200;
	}

	/**
	 * Determine if the response code was not found (404).
	 */
	public function not_found(): bool {
		return $this->status() === 404;
	}

	/**
	 * Determine if the response was a redirect.
	 */
	public function redirect(): bool {
		return $this->status() >= 300 && $this->status() < 400;
	}

	/**
	 * Determine if the response was a 401 "Unauthorized" response.
	 */
	public function unauthorized(): bool {
		return $this->status() === 401;
	}

	/**
	 * Determine if the response was a 403 "Forbidden" response.
	 */
	public function forbidden(): bool {
		return $this->status() === 403;
	}

	/**
	 * Determine if the response indicates a client or server error occurred.
	 */
	public function failed(): bool {
		return $this->server_error() || $this->client_error() || $this->is_wp_error();
	}

	/**
	 * Determine if the response indicates a client error occurred.
	 */
	public function client_error(): bool {
		return $this->status() >= 400 && $this->status() < 500;
	}

	/**
	 * Determine if the response indicates a server error occurred.
	 */
	public function server_error(): bool {
		return $this->status() >= 500;
	}

	/**
	 * Check if the error was an WP_Error.
	 */
	public function is_wp_error(): bool {
		return ! empty( $this->response['is_wp_error'] );
	}

	/**
	 * Check if the response is HTML.
	 */
	public function is_html(): bool {
		return false !== strpos( (string) $this->header( 'content-type' ), 'text/html' );
	}

	/**
	 * Check if the response is JSON.
	 */
	public function is_json(): bool {
		if ( false !== strpos( (string) $this->header( 'content-type' ), 'application/json' ) ) {
			return true;
		}

		return ! empty( $this->json() );
	}

	/**
	 * Check if the response is XML. Does not validate if the response is a valid
	 * XML document.
	 */
	public function is_xml(): bool {
		if ( false !== strpos( (string) $this->header( 'content-type' ), 'application/xml' ) ) {
			return true;
		}

		return str_starts_with( trim( strtolower( $this->body() ) ), '<?xml' );
	}

	/**
	 * Check if the response body is a file download (a Binary Large OBject).
	 */
	public function is_blob(): bool {
		return false === mb_detect_encoding( $this->body(), 'UTF-8', true ) && ! ctype_print( $this->body() );
	}

	/**
	 * Check if the response is a file download.
	 */
	public function is_file(): bool {
		return ! empty( $this->response['filename'] ) && $this->is_blob();
	}

	/**
	 * Get the raw body of the response.
	 */
	public function body(): string {
		return (string) ( $this->response['body'] ?? '' );
	}

	/**
	 * Retrieve the file path to the downloaded file.
	 */
	public function file(): ?string {
		return $this->response['filename'] ?? null;
	}

	/**
	 * Retrieve the file contents of the downloaded file.
	 */
	public function file_contents(): ?string {
		$file = $this->file();

		return empty( $file ) ? null : (string) file_get_contents( $file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	/**
	 * Get the JSON decoded body of the response as an array or scalar value.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 * @return mixed
	 */
	public function json( ?string $key = null, mixed $default = null ) {
		if ( $this->decoded === null ) {
			$this->decoded = json_decode( $this->body(), true );
		}

		if ( is_null( $key ) ) {
			return $this->decoded;
		}

		return data_get( $this->decoded, $key, $default );
	}

	/**
	 * Get the JSON decoded body of the response as a Mixed_Data instance.
	 *
	 * @param  string|null $key
	 * @param  mixed       $default
	 */
	public function mixed_json( ?string $key = null, mixed $default = null ): Mixed_Data {
		return Mixed_Data::of( $this->json( $key, $default ) );
	}

	/**
	 * Retrieve an instance of Assertable_Json_String to perform fluent JSON assertions.
	 *
	 * @param string|null $key Optional key to pass to `json()` to scope the JSON data.
	 */
	public function assertable_json( ?string $key = null ): Assertable_Json_String {
		return new Assertable_Json_String( $this->json( $key ) );
	}

	/**
	 * Get the body of the response as an HTML object.
	 */
	public function html(): HTML {
		return new HTML( $this->body() );
	}

	/**
	 * Get the XML body of the response.
	 *
	 * @template TDefault
	 *
	 * @param string|null $xpath Path to pass to `SimpleXMLElement::xpath()`, optional.
	 * @param mixed       $default Default value to return if the path does not exist.
	 * @return SimpleXMLElement|string|null Returns a specific SimpleXMLElement if path is specified, otherwise the entire document.
	 *
	 * @phpstan-param TDefault $default
	 * @phpstan-return ($xpath is null ? SimpleXMLElement : (array<SimpleXMLElement>|TDefault))
	 */
	public function xml( ?string $xpath = null, mixed $default = null ) {
		if ( ! $this->element instanceof \SimpleXMLElement ) {
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
	public function object(): mixed {
		return json_decode( $this->body(), false );
	}

	/**
	 * Get the JSON decoded body of the response as a collection.
	 *
	 * @param  string|null $key
	 * @return Collection<array-key, mixed>
	 */
	public function collect( ?string $key = null ): \Mantle\Support\Collection {
		return new Collection( $this->json( $key ) );
	}

	/**
	 * Retrieve the cookies from the response.
	 *
	 * @return WP_Http_Cookie[]
	 */
	public function cookies(): array {
		return $this->response['cookies'] ?? [];
	}

	/**
	 * Retrieve a specific cookie by name.
	 *
	 * @param string $name Cookie name.
	 */
	public function cookie( string $name ): ?WP_Http_Cookie {
		return collect( $this->cookies() )
			->key_by( 'name' )
			->get( $name );
	}

	/**
	 * Dump the response to the screen.
	 */
	public function dump(): static {
		dump( $this->response );
		return $this;
	}

	/**
	 * Dump the response to the screen and exit.
	 */
	public function dd(): never {
		$this->dump();
		exit( 1 );
	}

	/**
	 * Check if an attribute exists on the response.
	 *
	 * @param mixed $offset Offset to check.
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
	 */
	public function offsetGet( mixed $offset ): mixed {
		if ( is_null( $offset ) ) {
			return null;
		}

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
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		throw new LogicException( 'Response values are read-only.' );
	}

	/**
	 * Remove an attribute from the response.
	 *
	 * @throws LogicException Not supported on responses.
	 * @param mixed $offset Offset.
	 */
	public function offsetUnset( mixed $offset ): void {
		throw new LogicException( 'Response values are read-only.' );
	}

	/**
	 * Prepare the object for serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function __serialize(): array {
		// Purge some data from the response for lighter serialization.
		unset( $this->response['http_response'] );

		foreach ( [ 'cookies', 'filename', 'headers' ] as $key ) {
			if ( empty( $this->response[ $key ] ) ) {
				unset( $this->response[ $key ] );
			}
		}

		return [
			'url'      => $this->url,
			'response' => $this->response,
		];
	}

	/**
	 * Restore the object from serialized data.
	 *
	 * @throws LogicException If the serialized data is invalid.
	 *
	 * @param array<string, mixed> $data Serialized data.
	 */
	public function __unserialize( array $data ): void {
		if ( ! isset( $data['response'] ) || ! is_array( $data['response'] ) ) {
			throw new LogicException( 'Invalid serialized response data.' );
		}

		if ( isset( $data['url'] ) ) {
			$this->url = is_string( $data['url'] ) ? $data['url'] : null;
		}

		$this->response = $data['response'];
		$this->cached   = true;
	}
}
