<?php
/**
 * Mock_Http_Response_With_Snapshots trait file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Concerns\Snapshots;

use InvalidArgumentException;
use Mantle\Container\Container;
use Mantle\Http_Client\Request;
use Mantle\Http_Client\Response;
use Mantle\Testing\Mock_Http_Response;
use Mantle\Testing\TestCase;
use Mantle\Testing\Utils;
use ReflectionClass;
use Spatie\Snapshots\Drivers\JsonDriver;
use Spatie\Snapshots\Snapshot;

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
 */
trait Mock_Http_Response_With_Snapshots {
	/**
	 * Flag if a snapshot should be used to mock the response.
	 */
	public bool $snapshot = false;

	/**
	 * Internal Snapshot instance.
	 */
	private Snapshot $snapshot_instance;

	/**
	 * Internal Request instance.
	 */
	private Request $request;

	/**
	 * Generate a response from a snapshot file.
	 */
	public function with_snapshot(): static {
		$this->snapshot = true;

		return $this;
	}

	/**
	 * Fetch the snapshot from storage or make an actual request.
	 *
	 * @todo Add support for updating snapshots.
	 *
	 * @param Request $request
	 */
	public function process_from_snapshot( Request $request ): ?array {
		if ( ! $this->snapshot ) {
			throw new InvalidArgumentException( 'Snapshot not enabled for mocked request.' );
		}

		$this->request = $request;

		$this->snapshot_instance = $this->get_snapshot_for_request( $request );

		if ( ! $this->snapshot_instance->exists() ) {
			if ( Utils::is_ci() ) {
				$this->get_test_case()->fail(
					'Snapshot does not exist for a request that is being mocked with a snapshot: ' . $request->url(),
				);
			}

			// Add the filter that will capture the HTTP response for the snapshot.
			add_filter( 'http_response', [ $this, 'capture_http_response_for_snapshot' ], 10, 3 );

			return null;
		}

		$contents = wp_json_file_decode( $this->get_snapshot_path( $request ), [ 'associative' => true ] );

		if ( ! is_array( $contents ) ) {
			Utils::error( 'Snapshot file is not valid JSON: ' . $this->get_snapshot_path( $request ), 'HTTP Requests' );

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
	 * Retrieve the snapshot for the request.
	 *
	 * @param Request $request
	 */
	private function get_snapshot_for_request( Request $request ): Snapshot {
		return Snapshot::forTestCase(
			$this->get_snapshot_id( $request ),
			$this->get_snapshot_directory(),
			new JsonDriver(),
		);
	}

	/*
	 * Determines the snapshot's id. By default, the test case's class and
	 * method names are used.
	 */
	private function get_snapshot_id( Request $request ): string {
		$test_case = $this->get_test_case();

		return collect( [
			$test_case->nameWithDataSet(),
			$request->enum_method()->value,
			str_replace( [ '/', ':', DIRECTORY_SEPARATOR ], '-', $request->url() ),
		] )->join( '-' );
	}

	private function get_snapshot_path( Request $request ): string {
		return $this->get_snapshot_directory() . DIRECTORY_SEPARATOR . $this->get_snapshot_id( $request ) . '.json';
	}

	/*
	 * Determines the directory where snapshots are stored. By default a
	 * `__http_snapshots__` directory is created at the same level as the test
	 * class.
	 */
	private function get_snapshot_directory(): string {
		$reflection = new ReflectionClass( $this->get_test_case() );

		return collect( [
			dirname( $reflection->getFileName() ),
			'__http_snapshots__',
			$reflection->getShortName(),
		] )->join( DIRECTORY_SEPARATOR );
	}

	/**
	 * Store the HTTP response as a snapshot.
	 *
	 * @param array $response
	 * @param array $args
	 * @param string $url
	 * @return array
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

		$response = Response::create( $response )->response();

		Utils::info( 'Snapshot has been created for a mocked HTTP request to: ' . $url, 'HTTP Requests' );

		$this->snapshot_instance->create( $response );

		return $response;
	}
}
