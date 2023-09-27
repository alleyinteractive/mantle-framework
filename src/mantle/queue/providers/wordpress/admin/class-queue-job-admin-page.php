<?php
/**
 * Queue_Job_Admin_Page class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress\Admin;

use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Queue_Job;
use Mantle\Queue\Providers\WordPress\Queue_Worker_Job;

/**
 * Renders the queue admin page screen.
 *
 * @todo Refactor to use Blade and Mantle templating.
 */
class Queue_Job_Admin_Page {

	/**
	 * Render the admin page.
	 */
	public function render(): void {
		$job_id = ! empty( $_GET['job'] ) ? absint( $_GET['job'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		match ( true ) {
			! empty( $_GET['action'] ) && $job_id => $this->render_action( $job_id ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! empty( $job_id ) => $this->render_single_job( $job_id ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			default => $this->render_table(),
		};
	}

	/**
	 * Render a single job view.
	 *
	 * @param int $job_id The job ID.
	 */
	protected function render_single_job( int $job_id ): void {
		$job = Queue_Job::find( $job_id );

		if ( empty( $job ) ) {
			wp_die( esc_html__( 'Invalid job ID.', 'mantle' ) );
		}

		include __DIR__ . '/template/single.php';
	}

	/**
	 * Handle an action (retry/delete).
	 *
	 * @param int $job_id The job ID.
	 */
	protected function render_action( int $job_id ): void {
		if (
			empty( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'queue-job-action-' . $job_id )
		) {
			wp_die( 'Invalid nonce.' );
		}

		$action  = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job     = Queue_Job::find( $job_id );
		$message = '';

		if ( empty( $job ) ) {
			wp_die( esc_html__( 'Invalid job ID.', 'mantle' ) );
		}

		if ( 'retry' === $action ) {
			if ( Post_Status::FAILED->value !== $job->status ) {
				wp_die( esc_html__( 'Job is not in a failed state.', 'mantle' ) );
			}

			( new Queue_Worker_Job( $job ) )->retry();

			$message = esc_html__( 'Job has been scheduled to be retried.', 'mantle' );
		} elseif ( 'delete' === $action ) {
			$job->delete( true );

			$message = esc_html__( 'Job has been deleted.', 'mantle' );
		}

		if ( ! empty( $message ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( $message )
			);
		}

		$this->render_table();
	}

	/**
	 * Render the queue table.
	 */
	protected function render_table(): void {
		$table = new Queue_Jobs_Table();

		$table->prepare_items();

		include __DIR__ . '/template/table.php';
	}
}
