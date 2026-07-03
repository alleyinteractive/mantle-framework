<?php
/**
 * Mocks_Http_Requests trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns\Snapshots;

use InvalidArgumentException;
use Mantle\Container\Container;
use Mantle\Filesystem\Filesystem;
use Mantle\Http_Client\Request;
use Mantle\Http_Client\Response;
use Mantle\Support\Str;
use Mantle\Testing\TestCase;
use Mantle\Testing\Utils;
use ReflectionClass;

use function Mantle\Support\Helpers\collect;

/**
 * Mock HTTP requests with snapshots.
 *
 * Snapshots are stored on the filesystem and used as responses rather than
 * making an actual HTTP request. The snapshot will be generated on the first
 * run and then reused on subsequent runs. If a snapshot does not exist on a CI
 * test run, the test will fail.
 *
 * @mixin \Mantle\Testing\Mock_Http_Response
 * @phpstan-import-type CoreResponse from \Mantle\Http_Client\Response
 * @phpstan-import-type WpHttpRequestResponse from \Mantle\Http_Client\Response
 */
trait Mocks_Http_Requests {
	/**
	 * Flag if a snapshot should be used to mock the response.
	 *
	 * @var bool|non-empty-string
	 */
	public bool|string $snapshot = false;

	/**
	 * Path to snapshot file.
	 */
	private string $snapshot_file;

	/**
	 * Internal Request instance.
	 */
	private Request $request;

	/**
	 * Generate a response from a snapshot file.
	 *
	 * @throws InvalidArgumentException Thrown when the snapshot name is empty.
	 *
	 * @param bool|string $snapshot If true, the snapshot name will be generated
	 *                              from the request. The snapshot will converted
	 *                              to a slug with dashes.
	 *
	 *                              Use caution if you use to match multiple requests with a single
	 *                              snapshot ID.
	 *
	 * @phpstan-param bool|non-empty-string $snapshot
	 */
	public function with_snapshot( bool|string $snapshot = true ): static {
		if ( is_string( $snapshot ) && empty( $snapshot ) ) {
			throw new InvalidArgumentException( 'Snapshot name cannot be an empty string.' );
		}

		$this->snapshot = $snapshot;

		return $this;
	}

	/**
	 * Fetch the snapshot from storage or make an actual request.
	 *
	 * @throws InvalidArgumentException Thrown when called without snapshot being set to true.
	 *
	 * @param Request $request Current request object.
	 */
	public function process_from_snapshot( Request $request ): ?array {
		if ( ! $this->snapshot ) {
			throw new InvalidArgumentException( 'Snapshot not enabled for mocked request.' );
		}

		$this->request       = $request;
		$this->snapshot_file = $this->get_snapshot_path( $request );

		if ( $this->should_update_snapshots() || ! file_exists( $this->snapshot_file ) ) {
			if ( Utils::is_ci() ) {
				$this->get_test_case()->fail(
					'Snapshot does not exist for a request that is being mocked with a snapshot: ' . $request->url(),
				);
			}

			// Add the filter that will capture the HTTP response for the snapshot.
			add_filter( 'http_response', [ $this, 'capture_http_response_for_snapshot' ], 10, 3 );

			return null;
		}

		$contents = wp_json_file_decode( $this->snapshot_file, [ 'associative' => true ] );

		if ( ! is_array( $contents ) ) {
			Utils::error( 'Snapshot file is not valid JSON: ' . $this->snapshot_file, 'HTTP Requests' );

			return null;
		}

		return $contents;
	}

	/**
	 * Get the test case instance.
	 */
	private function get_test_case(): TestCase {
		return Container::get_instance()->make( TestCase::class );
	}

	/**
	 * Retrieve the snapshot path for the request.
	 *
	 * @param Request $request Request object.
	 */
	private function get_snapshot_path( Request $request ): string {
		return $this->get_snapshot_directory() . DIRECTORY_SEPARATOR . $this->get_snapshot_id( $request ) . '.json';
	}

	/**
	 * Determines the directory where snapshots are stored.
	 *
	 * By default a `__http_snapshots__` directory is created at the same level as
	 * the test class.
	 */
	private function get_snapshot_directory(): string {
		$reflection = new ReflectionClass( $this->get_test_case() );

		return collect( [
			dirname( (string) $reflection->getFileName() ),
			'__http_snapshots__',
			$reflection->getShortName(),
		] )->join( DIRECTORY_SEPARATOR );
	}

	/**
	 * Determines the snapshot's id. By default, the test case's class and
	 * method names are used.
	 *
	 * @param Request $request
	 */
	private function get_snapshot_id( Request $request ): string {
		$test_case = $this->get_test_case();

		$params = collect( [
			$test_case->nameWithDataSet(),
		] );

		if ( is_string( $this->snapshot ) ) {
			$params->push( $this->snapshot );

			return $params->join( '-' );
		}

		// Include the request in the snapshot ID if one wasn't provided.
		$params->push(
			strtolower( $request->enum_method()->value ),
			Str::slug( str_replace( [ '/', ':', DIRECTORY_SEPARATOR, '.' ], '-', $request->url() ) ),
		);

		if ( $request->body() ) {
			$params->push( md5( (string) wp_json_encode( $request->body() ) ) );
		}

		$headers = collect( $request->headers() )->except( 'content-type' )->all();

		if ( ! empty( $headers ) ) {
			$params->push( md5( (string) wp_json_encode( $headers ) ) );
		}

		return $params->join( '-' );
	}

	/**
	 * Determines whether or not the snapshot should be updated instead of
	 * matched.
	 *
	 * Mirrors the logic from spatie/phpunit-snapshot-assertions.
	 *
	 * Override this method it you want to use a different flag or mechanism
	 * than `-d --update-snapshots` or `UPDATE_SNAPSHOTS=true` env var.
	 */
	private function should_update_snapshots(): bool {
		if ( in_array( '--update-snapshots', $_SERVER['argv'], true ) ) { // phpcs:ignore
			return true;
		}

		return getenv( 'UPDATE_SNAPSHOTS' ) === 'true';
	}

	/**
	 * Store the HTTP response as a snapshot.
	 *
	 * @param CoreResponse $response Response from WordPress.
	 * @param array<mixed> $args Arguments for the request.
	 * @param string       $url URL for the request.
	 * @return CoreResponse
	 */
	public function capture_http_response_for_snapshot( array $response, array $args, string $url ): array {
		if ( ! isset( $this->request ) ) {
			return $response;
		}

		// Ensure the response matches the HTTP request we are looking for.
		if ( $args['method'] !== $this->request->method() || $this->request->url() !== $url ) {
			return $response;
		}

		// Remove the one-time filter.
		remove_filter( 'http_response', [ $this, 'capture_http_response_for_snapshot' ], 10 );

		$filesystem = new Filesystem();

		$filesystem->ensure_directory_exists( dirname( $this->snapshot_file ) );
		$filesystem->put_json( $this->snapshot_file, Response::create( $response )->response() );

		Utils::info( 'Snapshot has been created for a mocked HTTP request to: ' . $url, 'HTTP Requests' );

		return $response;
	}
}
