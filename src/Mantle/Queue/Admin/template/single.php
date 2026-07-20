<?php
/**
 * Render the view for a single job.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package Mantle
 * @var \Mantle\Queue\Jobs\Database_Job_Record $record
 */

use Carbon\Carbon;
use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;

if ( ! isset( $record ) || ! $record instanceof Database_Job_Record ) { // @phpstan-ignore-line instanceof.alwaysTrue
	esc_html_e( 'Invalid job record.', 'mantle' );
	return;
}

$log = is_array( $record->log ) ? $record->log : json_decode( $record->log, true ); // @phpstan-ignore-line
$job = $record->job();

if ( ! is_array( $log ) ) {
	$log = [];
}
?>
<div class="wrap">
	<h1>
		<?php
		printf(
			/* translators: %s: The job ID. */
			esc_html__( 'Mantle Queue: Job #%1$s — %2$s', 'mantle' ),
			(int) $record->id,
			/* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */
			match ( Status::from( $record->status ) ) {
				Status::PENDING => esc_html__( 'Pending', 'mantle' ),
				Status::FAILED => esc_html__( 'Failed', 'mantle' ),
				Status::RUNNING => esc_html__( 'Running', 'mantle' ),
				default => esc_html__( 'Unknown', 'mantle' ),
			},
			/* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */
		);
		?>
	</h1>
	<p>
		<a href="<?php menu_page_url( 'mantle-queue', true ); ?>">
			<?php esc_html_e( 'Back to Queue Jobs', 'mantle' ); ?>
		</a>
	</p>

	<hr class="wp-header-end">

	<section class="queue-job-container">
		<aside>
			<div>
				<?php if ( Status::PENDING->value === $record->status ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $record->id ),
									'filter'   => false,
									'job'      => (int) $record->id,
									'action'   => 'run',
								]
							)
						);
						?>
					"
						aria-label="<?php esc_attr_e( 'Run this job', 'mantle' ); ?>"
						class="button button-primary"
					>
						<?php esc_html_e( 'Run', 'mantle' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( Status::FAILED->value === $record->status ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $record->id ),
									'filter'   => false,
									'job'      => (int) $record->id,
									'action'   => 'retry',
								]
							)
						);
						?>
					"
						aria-label="<?php esc_attr_e( 'Retry this job', 'mantle' ); ?>"
						class="button button-primary"
					>
						<?php esc_html_e( 'Retry', 'mantle' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( Status::RUNNING->value !== $record->status && ! $record->is_locked() ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $record->id ),
									'filter'   => false,
									'job'      => (int) $record->id,
									'action'   => 'delete',
								]
							)
						);
						?>
					"
						aria-label="<?php esc_attr_e( 'Delete this job', 'mantle' ); ?>"
						class="button button-delete"
						onclick="return confirm('<?php echo esc_attr__( 'Are you sure you want to delete this job?', 'mantle' ); ?>');"
					>
						<?php esc_html_e( 'Delete', 'mantle' ); ?>
					</a>
				<?php endif; ?>
			</div>
			<h3><?php esc_html_e( 'Job', 'mantle' ); ?></h3>
			<p>
				<code><?php echo esc_html( $job->get_id() ); ?></code>
			</p>
			<h3><?php esc_html_e( 'Arguments', 'mantle' ); ?></h3>
			<code>
				<?php echo wp_json_encode( is_object( $job->get_job() ) ? get_object_vars( $job->get_job() ) : '' ); ?>
			</code>
			<h3><?php esc_html_e( 'Queue', 'mantle' ); ?></h3>
			<p>
				<code><?php echo esc_html( $record->queue ); ?></code>
			</p>
			<?php if ( Status::COMPLETED->value !== $record->status ) : ?>
				<h3><?php esc_html_e( 'Lock Status', 'mantle' ); ?></h3>
				<p>
					<?php if ( $record->is_locked() ) : ?>
						<?php
						printf(
							/* translators: %s: The time the job is locked until. */
							esc_html__( 'Locked until %s', 'mantle' ),
							esc_html( $record->get_lock_until()->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
						);
						?>
					<?php else : ?>
						<?php esc_html_e( 'Unlocked', 'mantle' ); ?>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</aside>

		<section>
			<h3><?php esc_html_e( 'Job Log', 'mantle' ); ?></h3>
			<?php if ( empty( $log ) ) : ?>
				<p><?php esc_html_e( 'No log entries found for this job.', 'mantle' ); ?></p>
			<?php endif; ?>
			<ol>
				<?php foreach ( $log as $entry ) : ?>
					<li>
						<p>
							<strong>
								<?php echo esc_html( ucwords( $entry['event'] ?? '' ) ); ?>
							</strong>
							&mdash;
							<?php $time = Carbon::createFromTimestampUTC( $entry['time'] ?? 0 ); ?>
							<?php echo esc_html( $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?>
							<span title="<?php echo esc_attr( $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?>">
								&mdash;
								<?php echo esc_html( $time->diffForHumans() ); ?>
							</span>
						</p>

						<?php if ( ! empty( $entry['payload'] ) ) : ?>
							<pre><?php echo wp_json_encode( $entry['payload'], JSON_PRETTY_PRINT ); ?></pre>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
	</section>
</div>

<style type="text/css">
	@media screen and (min-width: 782px) {
		.queue-job-container {
			display: flex;
			flex-direction: row;
		}

		.queue-job-container > * {
			width: 50%;
		}
	}

	.queue-job-container .button-delete {
		border-color: #d63638;
		background: #d63638;
		color: white;
	}

	.queue-job-container .button-delete:hover {
		border-color: #d63638;
		color: #d63638;
	}

	.queue-job-container pre {
		background: #D4D4D4;
		border-radius: 0.25rem;
		margin: 0;
		overflow-x: scroll;
		padding: 1rem;
	}
</style>
