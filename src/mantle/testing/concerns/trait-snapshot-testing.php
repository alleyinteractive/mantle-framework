<?php
/**
 * Snapshot_Testing trait file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Support\Str;

/**
 * Snapshot Testing
 *
 * @mixin \Mantle\Testing\Test_Response
 */
trait Snapshot_Testing {
	/**
	 * Assert that the response matches a stored snapshot comparing only the content.
	 *
	 * Alias to `assertMatchesSnapshotContent()`.
	 *
	 * @return static
	 */
	public function assertMatchesSnapshot(): static {
		return $this->assertMatchesSnapshotContent();
	}

	/**
	 * Assert that the response's content matches a stored snapshot.
	 *
	 * Checks the response content-type to use the proper driver to make the
	 * assertion against.
	 *
	 * @return static
	 */
	public function assertMatchesSnapshotContent(): static {
		if ( $this->test_case ) {
			$content_type = $this->get_header( 'content-type' );

			return match ( true ) {
				Str::contains( $content_type, 'application/json', true ) => $this->assertMatchesSnapshotJson(),
				Str::contains( $content_type, 'text/html', true ) => $this->assertMatchesSnapshotHtml(),
				default => $this->test_case->assertMatchesSnapshot( $this->get_content() ),
			};
		}

		return $this;
	}

	/**
	 * Assert that the response's HTML content matches a stored snapshot.
	 *
	 * @return static
	 */
	public function assertMatchesSnapshotHtml(): static {
		if ( $this->test_case ) {
			$this->test_case->assertMatchesHtmlSnapshot( $this->get_content() );
		}

		return $this;
	}

	/**
	 * Assert that the response's JSON content matches a stored snapshot.
	 *
	 * @return static
	 */
	public function assertMatchesSnapshotJson(): static {
		if ( $this->test_case ) {
			$this->test_case->assertMatchesJsonSnapshot( $this->get_content() );
		}

		return $this;
	}

	/**
	 * Assert that the response matches the stored snapshot, comparing status
	 * code, headers, and content.
	 *
	 * **Note:** asserting against the headers of a response can lead to leaky tests
	 * that break not too long after they are written. `assertMatchesSnapshotContent()`
	 * is a better alternative.
	 *
	 * @return static
	 */
	public function assertMatchesSnapshotWithStatusAndHeaders(): static {
		return $this
			->assertStatusAndHeadersMatchSnapshot()
			->assertMatchesSnapshotContent();
	}

	/**
	 * Assert that the response's status code and headers match a stored snapshot.
	 *
	 * @return static
	 */
	public function assertStatusAndHeadersMatchSnapshot(): static {
		if ( $this->test_case ) {
			$this->test_case->assertMatchesSnapshot(
				[
					$this->get_status_code(),
					$this->get_headers(),
				]
			);
		}

		return $this;
	}
}
