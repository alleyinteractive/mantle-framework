<?php
/**
 * Remote_Request_Collector class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Query_Monitor\Collector;

use InvalidArgumentException;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Support\Traits\Hookable;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use WP_Error;

use function Mantle\Support\Helpers\collect;

/**
 * Remote Request Collector
 *
 * @phpstan-import-type CoreResponse from \Mantle\Http_Client\Response
 *
 * @phpstan-type CollectedHttpRequest array{
 *   args: array<mixed>,
 *   key?: string,
 *   start: float,
 *   trace: array<int, Frame>,
 *   url: string,
 * }
 *
 * @phpstan-type CollectedHttpResponse array{
 *   args: array<mixed>,
 *   response: CoreResponse|Response|WP_Error,
 *   stop: float,
 *   url: string,
 * }
 *
 * @phpstan-type CollectedHttpEntry array{
 *   args: array<mixed>,
 *   error: bool,
 *   key?: string,
 *   response: \Mantle\Http_Client\Response,
 *   shortcircuited: bool,
 *   start: float,
 *   stop: float,
 *   trace: array<int, Frame>,
 *   url: string,
 * }
 */
class Remote_Request_Collector extends \QM_Collector {
	use Hookable;

	/**
	 * Key to mark short circuited requests.
	 */
	private const SHORTCIRCUIT_KEY = '_short_circuited';

	/**
	 * Collector ID
	 *
	 * @var string
	 */
	public $id = 'mantle-remote-request';

	/**
	 * @var Remote_Request_Data_Collector
	 */
	protected $data;

	/**
	 * Http Requests
	 *
	 * @var array<string, CollectedHttpRequest>
	 */
	public array $requests = [];

	/**
	 * Http Responses
	 *
	 * @var array<string, CollectedHttpResponse>
	 */
	public array $responses = [];

	/**
	 * Get the filters this collector is concerned with.
	 *
	 * @return string[]
	 */
	public function get_concerned_filters(): array {
		return [
			'http_request_args',
			'pre_http_request',
		];
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->register_hooks();
	}

	/**
	 * Get the storage class for the collector.
	 */
	public function get_storage(): Remote_Request_Data_Collector {
		return new Remote_Request_Data_Collector();
	}

	/**
	 * Get the collector data.
	 */
	public function get_data(): Remote_Request_Data_Collector {
		/** @var Remote_Request_Data_Collector $data */
		$data = parent::get_data();

		return $data;
	}

	/**
	 * Filter the HTTP request arguments to inject a collector key and store a trace.
	 *
	 * @param array<mixed> $args HTTP request arguments.
	 * @param string       $url Request URL.
	 * @return array<mixed> Filtered HTTP request arguments.
	 */
	#[Filter( 'http_request_args', 999999999 )]
	public function inject_collector_key_to_args( array $args, string $url ): array {
		// Inject the request ID for tracking. This applies to non-Mantle HTTP
		// client requests as Pending_Request will inject it for its requests.
		if ( ! isset( $args[ Pending_Request::REQUEST_ID_KEY ] ) ) {
			$start = microtime( true );

			$args[ Pending_Request::REQUEST_ID_KEY ] = $this->generate_request_id( $url, $start );
		} else {
			$parts = is_string( $args[ Pending_Request::REQUEST_ID_KEY ] )
				? explode( ':', $args[ Pending_Request::REQUEST_ID_KEY ], 2 )
				: [];

			// Retrieve the start time from the request ID.
			$start = isset( $parts[0] ) && is_numeric( $parts[0] ) ? (float) $parts[0] : microtime( true );
		}

		$request_id = $args[ Pending_Request::REQUEST_ID_KEY ];

		$this->requests[ $request_id ] = [
			'start' => $start,
			'url'   => $url,
			'args'  => $args,
			'trace' => $this->get_trace(),
		];

		return $args;
	}

	/**
	 * Collect remote request data for short circuited requests.
	 *
	 * @param false|\WP_Error|CoreResponse $response HTTP response or false on failure.
	 * @param array                        $args HTTP request arguments.
	 * @param string                       $url Request URL.
	 * @return mixed The original response.
	 */
	#[Filter( 'pre_http_request', 999999999 )]
	public function collect_remote_request( mixed $response, array $args, string $url ): mixed {
		if ( false === $response ) {
			return $response;
		}

		// Mark the request as short circuited.
		$args[ self::SHORTCIRCUIT_KEY ] = true;

		$this->store_http_response( $response, $args, $url );

		return $response;
	}

	/**
	 * Listen for the http_api_debug action to collect HTTP responses.
	 *
	 * @param mixed        $argument The value passed to the filter.
	 * @param string       $context The context of the filter.
	 * @param string       $class The class name.
	 * @param array<mixed> $parsed_args The parsed arguments.
	 * @param string       $url The request URL.
	 */
	#[Action( 'http_api_debug' )]
	public function listen_for_http_api_debug( mixed $argument, string $context, string $class, array $parsed_args, string $url ): void {
		if ( 'response' === $context && is_array( $argument ) ) {
			$this->store_http_response( $argument, $parsed_args, $url );
		}
	}

	/**
	 * Collect cached HTTP responses.
	 *
	 * @throws InvalidArgumentException If the Pending_Request is missing the required request ID argument for tracking.
	 *
	 * @param Pending_Request $request The HTTP request.
	 * @param Response        $response The HTTP response.
	 * @param string          $cache_key The cache key used.
	 */
	#[Action( 'mantle_http_client_cache_hit' )]
	public function collect_cached_http_response( Pending_Request $request, Response $response, string $cache_key ): void {
		$arguments = $request->get_request_args();

		if ( ! isset( $arguments[ Pending_Request::REQUEST_ID_KEY ] ) ) {
			throw new InvalidArgumentException(
				'The Pending_Request is missing the required request ID argument for tracking.',
			);
		}

		$request_id = $arguments[ Pending_Request::REQUEST_ID_KEY ];

		$this->requests[ $request_id ] = [
			'args'  => $arguments,
			'key'   => $cache_key,
			'start' => microtime( true ),
			'trace' => $this->get_trace(),
			'url'   => $request->url(),
		];

		$this->store_http_response( $response, $arguments, $request->url() );
	}

	/**
	 * Record newly cached HTTP responses.
	 *
	 * @param Pending_Request $request
	 * @param Response        $response
	 */
	#[Action( 'mantle_http_client_cached' )]
	public function collect_newly_cached_http_response( Pending_Request $request, Response $response ): void {
		$this->store_http_response( $response, $request->get_request_args(), $request->url() );
	}

	/**
	 * Store the HTTP response data.
	 *
	 * @param array|Response|WP_Error|false $response HTTP response.
	 * @param array<mixed>                  $args HTTP request arguments.
	 * @param string                        $url Request URL.
	 */
	protected function store_http_response( array|Response|WP_Error|false $response, array $args, string $url ): void {
		if ( ! isset( $args[ Pending_Request::REQUEST_ID_KEY ] ) ) {
			return;
		}

		$request_id = $args[ Pending_Request::REQUEST_ID_KEY ];

		$this->responses[ $request_id ] = [
			'args'     => $args,
			'response' => $response,
			'stop'     => microtime( true ),
			'url'      => $url,
		];
	}

	/**
	 * Setup the collector data.
	 */
	public function process(): void {
		if ( empty( $this->requests ) && empty( $this->responses ) ) {
			return;
		}

		foreach ( $this->requests as $key => $request ) {
			// Provide a timeout response if none exists).
			if ( ! isset( $this->responses[ $key ] ) ) {
				$this->responses[ $key ] = [
					'args'     => $request['args'],
					'response' => new WP_Error(
						'http_request_timed_out',
						__( 'The HTTP request did not receive a response before the timeout period expired.', 'mantle' ),
					),
					'stop'     => floatval( $request['start'] + ( $request['args']['timeout'] ?? 0 ) ),
					'url'      => $request['url'],
				];
			}

			// Convert the response to a Http Client Response instance.
			$response = match ( true ) {
				$this->responses[ $key ]['response'] instanceof Response => $this->responses[ $key ]['response'],
				is_array( $this->responses[ $key ]['response'] ) => Response::create( $this->responses[ $key ]['response'] ),
				is_wp_error( $this->responses[ $key ]['response'] ) => Response::create( $this->responses[ $key ]['response'] ),
				default => null,
			};

			if ( ! $response instanceof Response ) {
				continue;
			}

			$this->data->requests[ $key ] = [
				'args'           => $request['args'],
				'error'          => $response->is_wp_error() || $response->status() >= 400,
				'key'            => $request['key'] ?? null,
				'response'       => $response,
				'shortcircuited' => ! empty( $request['args'][ self::SHORTCIRCUIT_KEY ] ),
				'start'          => $request['start'],
				'stop'           => $this->responses[ $key ]['stop'],
				'trace'          => $request['trace'],
				'url'            => $request['url'],
			];
		}
	}

	/**
	 * Get the backtrace frames.
	 *
	 * @return array<int, Frame> Backtrace frames.
	 */
	protected function get_trace(): array {
		$trace = Backtrace::create()->startingFromFrame(
			function ( Frame $frame ): bool {
				if ( $frame->class !== Pending_Request::class ) {
					return false;
				}

				return in_array( $frame->method, [ 'get', 'post', 'delete', 'put', 'patch', 'head' ], true );
			}
		)->frames();

		// If no frames found, get the full trace from wp_remote_*().
		if ( empty( $trace ) ) {
			$trace = Backtrace::create()->startingFromFrame(
				fn ( Frame $frame ): bool => str_starts_with( (string) $frame->method, 'wp_remote_' ),
			)->frames();
		}

		$trace = collect( $trace );

		// Skip if the http_client() function helper was used.
		if ( $trace->contains(
			'method',
			'=',
			\Mantle\Http_Client\http_client::class,
		) ) {
			$trace = $trace->skip_until(
				fn ( Frame $frame ): bool => $frame->method === \Mantle\Http_Client\http_client::class,
			);
		}

		return $trace->slice( 1 )->values()->all();
	}

	/**
	 * Generate a unique request ID.
	 *
	 * @param string     $url Request URL.
	 * @param float|null $start Start time.
	 */
	private function generate_request_id( string $url, ?float $start = null ): string {
		$start ??= microtime( true );

		return "{$start}:{$url}:" . uniqid();
	}
}
