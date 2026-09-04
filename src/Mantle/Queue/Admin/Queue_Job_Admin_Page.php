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
	 * Render the admin page.
	 */
	public function render(): void {
		$job_id = empty( $_GET['job'] ) ? 0 : sanitize_text_field( wp_unslash( $_GET['job'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		match ( true ) {
			! empty( $_GET['action'] ) && $job_id => $this->render_action( $job_id ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			! empty( $job_id ) => $this->render_single_job( $job_id ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			default => $this->render_table(),
		};
	}

	/**
	 * Render a single job view.
	 *
	 * @param mixed $job_id The job ID.
	 */
	protected function render_single_job( mixed $job_id ): void {
		if ( ! is_numeric( $job_id ) ) {
			esc_html_e( 'Invalid job ID.', 'mantle' );
			return;
		}

		$record = Database_Job_Record::find( $job_id );

		if ( ! $record instanceof \Mantle\Queue\Jobs\Database_Job_Record ) {
			esc_html_e( 'Unknown job ID.', 'mantle' );
			return;
		}

		include __DIR__ . '/template/single.php';
	}

	/**
	 * Handle an action (retry/delete).
	 *
	 * @param mixed $job_id The job ID.
	 */
	protected function render_action( mixed $job_id ): void {
		if (
			empty( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'queue-job-action-' . $job_id )
		) {
			esc_html_e( 'Invalid nonce.', 'mantle' );
			return;
		}

		if ( ! is_numeric( $job_id ) ) {
			esc_html_e( 'Invalid job ID.', 'mantle' );
			return;
		}

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$record = Database_Job_Record::find( $job_id );

		if ( ! $record instanceof \Mantle\Queue\Jobs\Database_Job_Record ) {
			esc_html_e( 'Unknown job ID.', 'mantle' );
			return;
		}

		$message      = '';
		$message_link = '';

		$return_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				add_query_arg(
					[
						'action' => null,
						'job'    => null,
					],
				),
			),
			esc_html__( 'Return to queue jobs.', 'mantle' ),
		);

		if ( 'run' === $action ) {
			if ( Status::PENDING->value !== $record->status ) {
				wp_die( esc_html__( 'Job is not in a pending state.', 'mantle' ) . ' ' . $return_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			// Check if the job is locked.
			if ( $record->is_locked() ) {
				wp_die( esc_html__( 'Job is currently locked.', 'mantle' ) . ' ' . $return_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$job = $record->job();

			// Lock the job before it is run.
			$record->set_lock_until( $job->get_job()->timeout ?? 600 );

			// Run the queue job through the queue worker and refresh the record.
			app( Worker::class )->run_single( $job );

			$message = match ( $record->refresh()?->status ) {
				Status::FAILED->value => esc_html__( 'Job has failed.', 'mantle' ),
				Status::COMPLETED->value => esc_html__( 'Job has completed successfully.', 'mantle' ),
				default => esc_html__( 'Job has been run but the status is unknown.', 'mantle' ),
			};

			$message_status = Status::FAILED->value === $record->status ? 'error' : 'success';

			$message_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'action'   => null,
							'filter'   => null,
							'job'      => $record->id,
							'_wpnonce' => null,
						],
					),
				),
				esc_html__( 'View Details', 'mantle' ),
			);
		} elseif ( 'retry' === $action ) {
			if ( Status::FAILED->value !== $record->status ) {
				wp_die( esc_html__( 'Job is not in a failed state and cannot be retried.', 'mantle' ) );
			}

			$record->job()->retry();

			$message = esc_html__( 'Job has been scheduled to be retried.', 'mantle' );
		} elseif ( 'delete' === $action ) {
			$record->delete( true );

			$message = esc_html__( 'Job has been deleted.', 'mantle' );
		}

		if ( ! empty( $message ) ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $message_status ?? 'success' ),
				esc_html( $message ) . " {$message_link}", // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
