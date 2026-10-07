<?php
/**
 * Queue_Job_Admin_Page class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Admin;

use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;
use Mantle\Queue\Worker;

/**
 * Renders the queue admin page screen.
 */
class Queue_Job_Admin_Page {
	/**
	 * Handle a job action (run/retry/delete) and redirect back to the queue.
	 *
	 * Runs on the page's load hook so the action URL is never left in the
	 * browser's address bar to be replayed on refresh.
	 */
	public function handle_action(): void {
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$job_id = absint( $_GET['job'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! in_array( $action, [ 'run', 'retry', 'delete' ], true ) || ! $job_id ) {
			return;
		}

		check_admin_referer( 'queue-job-action-' . $job_id );

		$return_url = remove_query_arg( [ '_wpnonce', 'action', 'job', 'message' ] );
		$record     = Database_Job_Record::find( $job_id );

		if ( ! $record instanceof Database_Job_Record ) {
			wp_die( esc_html__( 'Unknown job ID.', 'mantle' ), '', [ 'back_link' => true ] );
		}

		$message = match ( $action ) {
			'run' => $this->run( $record ),
			'retry' => $this->retry( $record ),
			'delete' => $this->delete( $record ),
		};

		$args = [ 'message' => $message ];

		if ( 'run' === $action ) {
			$args['job'] = $record->id;
		}

		wp_safe_redirect( add_query_arg( $args, $return_url ) );
		exit;
	}

	/**
	 * Run a pending job immediately.
	 *
	 * @param Database_Job_Record $record The job record.
	 * @return string The message key to display.
	 */
	protected function run( Database_Job_Record $record ): string {
		if ( Status::PENDING->value !== $record->status ) {
			wp_die( esc_html__( 'Job is not in a pending state.', 'mantle' ), '', [ 'back_link' => true ] );
		}

		$job = $record->job();

		if ( ! $record->claim( now()->addSeconds( $job->get_job()->timeout ?? 600 ), force: true ) || ! $job->reserve() ) {
			wp_die( esc_html__( 'Job is currently being processed by another worker.', 'mantle' ), '', [ 'back_link' => true ] );
		}

		app( Worker::class )->run_single( $job );

		return match ( $record->refresh()?->status ) {
			Status::COMPLETED->value => 'run-completed',
			Status::PENDING->value => 'run-retrying',
			default => 'run-failed',
		};
	}

	/**
	 * Retry a failed job.
	 *
	 * @param Database_Job_Record $record The job record.
	 * @return string The message key to display.
	 */
	protected function retry( Database_Job_Record $record ): string {
		if ( Status::FAILED->value !== $record->status ) {
			wp_die( esc_html__( 'Job is not in a failed state and cannot be retried.', 'mantle' ), '', [ 'back_link' => true ] );
		}

		// A manual retry gets a fresh set of attempts.
		$record->save( [ 'attempts' => 0 ] );
		$record->job()->retry();

		return 'retried';
	}

	/**
	 * Delete a job.
	 *
	 * @param Database_Job_Record $record The job record.
	 * @return string The message key to display.
	 */
	protected function delete( Database_Job_Record $record ): string {
		if ( $record->is_locked() ) {
			wp_die( esc_html__( 'Job is currently running and cannot be deleted.', 'mantle' ), '', [ 'back_link' => true ] );
		}

		$record->delete( true );

		return 'deleted';
	}

	/**
	 * Render the admin page.
	 */
	public function render(): void {
		$this->render_notice();

		$job_id = absint( $_GET['job'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $job_id ) {
			$this->render_single_job( $job_id );
		} else {
			$this->render_table();
		}
	}

	/**
	 * Render the notice for a completed action.
	 */
	protected function render_notice(): void {
		$message = sanitize_text_field( wp_unslash( $_GET['message'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Links on the page are built from the current URL and would otherwise carry the notice along.
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'message' );

		[ $status, $text ] = match ( $message ) {
			'run-completed' => [ 'success', __( 'Job has completed successfully.', 'mantle' ) ],
			'run-failed' => [ 'error', __( 'Job has failed.', 'mantle' ) ],
			'run-retrying' => [ 'warning', __( 'Job has failed and has been scheduled to be retried.', 'mantle' ) ],
			'retried' => [ 'success', __( 'Job has been scheduled to be retried.', 'mantle' ) ],
			'deleted' => [ 'success', __( 'Job has been deleted.', 'mantle' ) ],
			default => [ null, null ],
		};

		if ( ! $status || ! $text ) {
			return;
		}

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $status ),
			esc_html( $text ),
		);
	}

	/**
	 * Render a single job view.
	 *
	 * @param int $job_id The job ID.
	 */
	protected function render_single_job( int $job_id ): void {
		$record = Database_Job_Record::find( $job_id );

		if ( ! $record instanceof Database_Job_Record ) {
			esc_html_e( 'Unknown job ID.', 'mantle' );
			return;
		}

		include __DIR__ . '/template/single.php';
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
