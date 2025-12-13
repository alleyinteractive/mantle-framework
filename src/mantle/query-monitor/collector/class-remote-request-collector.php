<?php
/**
 * Remote_Request_Collector class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Query_Monitor\Collector;

use Mantle\Contracts\Application;
use Mantle\Http\Request;
use Mantle\Http_Client\Pending_Request;
use Mantle\Http_Client\Response;
use Mantle\Log\Events\Message_Logged;
use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Support\Collection;
use Mantle\Support\Traits\Hookable;
use Monolog\Logger;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use WP_Error;

use function Mantle\Http_Client\http_client;
use function Mantle\Support\Helpers\collect;

/**
 * Remote Request Collector
 *
 * @phpstan-import-type CoreResponse from \Mantle\Http_Client\Response
 *
 * @phpstan-type CollectedHttpRequest array{
 *   start: float,
 *   url: string,
 *   args: array<mixed>,
 *   trace: array<int, Frame>,
 * }
 *
 * @phpstan-type CollectedHttpResponse array{
 *   args: array<mixed>,
 *   response: CoreResponse|WP_Error,
 *   stop: float,
 *   url: string,
 * }
 *
 * @phpstan-type CollectedHttpEntry array{
 *   args: array<mixed>,
 *   error: bool,
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

	private const COLLECTOR_KEY = '_mantle_collector';

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
	 *
	 * @param Application $app Application instance.
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
	 *
	 * @return Remote_Request_Data_Collector
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
		$start = microtime( true );

		if ( ! isset( $args[self::COLLECTOR_KEY] ) ) {
			$args[self::COLLECTOR_KEY] = "{$start}{$url}";
		}

		$this->requests[ $args[self::COLLECTOR_KEY] ] = [
			'start' => $start,
			'url'  => $url,
			'args' => $args,
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
	 * @param mixed  $argument The value passed to the filter.
	 * @param string $context The context of the filter.
	 * @param string $class The class name.
	 * @param array<mixed>  $parsed_args The parsed arguments.
	 * @param string $url The request URL.
	 */
	#[Action( 'http_api_debug' )]
	public function listen_for_http_api_debug( mixed $argument, string $context, string $class, array $parsed_args, string $url ): void {
		if ( 'response' == $context && is_array( $argument ) ) {
			$this->store_http_response( $argument, $parsed_args, $url );
		}
	}

	/**
	 * Store the HTTP response data.
	 *
	 * @param CoreResponse|WP_Error|false $response HTTP response.
	 * @param array<mixed>                $args HTTP request arguments.
	 * @param string                      $url Request URL.
	 */
	protected function store_http_response( mixed $response, array $args, string $url ): void {
		if ( ! isset( $args[self::COLLECTOR_KEY] ) ) {
			return;
		}

		$this->responses[$args[self::COLLECTOR_KEY]] = [
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
				$response = [
					'args'     => $request['args'],
					'response' => new WP_Error(
						'http_request_timed_out',
						__( 'The HTTP request did not receive a response before the timeout period expired.', 'mantle' ),
					),
					'stop'     => floatval( $request['start'] + $response['args']['timeout'] ),
					'url'      => $request['url'],
				];
			}

			// Convert the response to a Http Client Response instance.
			$response = Response::create( $this->responses[ $key ]['response'] );

			$this->data->requests[ $key ] = [
				'args'	          => $request['args'],
				'error'          => $response->is_wp_error() || $response->status() >= 400,
				'response'       => $response,
				'shortcircuited' => ! empty( $request['args'][self::SHORTCIRCUIT_KEY] ),
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
		} )->frames();

		// If no frames found, get the full trace from wp_remote_*().
		if ( empty( $trace ) ) {
			$trace = Backtrace::create()->startingFromFrame(
				fn ( Frame $frame ): bool => str_starts_with( $frame->method, 'wp_remote_' ),
			)->frames();
		}

		$trace = collect( $trace );

		// Skip if the http_client() function helper was used.
		if ( $trace->contains(
			'method',
			'=',
			'Mantle\Http_Client\http_client',
		) ) {
			$trace = $trace->skip_until(
				fn ( Frame $frame ): bool => $frame->method === 'Mantle\Http_Client\http_client',
			);
		}

		return $trace->slice( 1 )->values()->all();
	}
}
