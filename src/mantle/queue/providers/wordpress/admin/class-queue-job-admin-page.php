<?php
/**
 * Queue_Job_Admin_Page class file.
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress\Admin;

use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Queue_Record;
use Mantle\Queue\Providers\WordPress\Queue_Worker_Job;
use Mantle\Queue\Worker;

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
		$job = Queue_Record::find( $job_id );

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

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$record = Queue_Record::find( $job_id );

		$message      = '';
		$message_link = '';

		if ( empty( $record ) ) {
			wp_die( esc_html__( 'Invalid job ID.', 'mantle' ) );
		}

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
			if ( Post_Status::PENDING->value !== $record->status ) {
				wp_die( esc_html__( 'Job is not in a pending state.', 'mantle' ) . ' ' . $return_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			// Check if the job is locked.
			if ( $record->is_locked() ) {
				wp_die( esc_html__( 'Job is currently locked.', 'mantle' ) . ' ' . $return_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$job = new Queue_Worker_Job( $record );

			// Lock the job before it is run.
			$record->set_lock_until( $job->get_job()->timeout ?? 600 );

			// Run the queue job through the queue worker and refresh the record.
			app( Worker::class )->run_single( $job );

			$message = match ( $record->refresh()?->status ) {
				Post_Status::FAILED->value => esc_html__( 'Job has failed.', 'mantle' ),
				Post_Status::COMPLETED->value => esc_html__( 'Job has completed successfully.', 'mantle' ),
				default => esc_html__( 'Job has been run but the status is unknown.', 'mantle' ),
			};

			$message_status = Post_Status::FAILED->value === $record->status ? 'error' : 'success';

			$message_link = sprintf(
				'<a href="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'action' => null,
							'filter' => null,
							'job'    => $record->id(),
						],
					),
				),
				esc_html__( 'View Details', 'mantle' ),
			);
		} elseif ( 'retry' === $action ) {
			if ( Post_Status::FAILED->value !== $record->status ) {
				wp_die( esc_html__( 'Job is not in a failed state and cannot be retried.', 'mantle' ) );
			}

			( new Queue_Worker_Job( $record ) )->retry();

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
