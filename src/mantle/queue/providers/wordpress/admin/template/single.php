<?php
/**
 * Render the view for a single job.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package Mantle
 * @var \Mantle\Queue\Providers\WordPress\Queue_Record $job The queue job.
 */

use Carbon\Carbon;
use Mantle\Queue\Providers\WordPress\Meta_Key;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Queue_Worker_Job;

$worker_job = new Queue_Worker_Job( $job );

$log = $job->get_meta( Meta_Key::LOG->value, true );
$log = is_array( $log ) ? $log : [];

?>
<div class="wrap">
	<h1>
		<?php
		printf(
			/* translators: %s: The job ID. */
			esc_html__( 'Mantle Queue: Job #%1$s — %2$s', 'mantle' ),
			(int) $job->id,
			/* phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped */
			match ( $job->status ) {
				Post_Status::PENDING->value => esc_html__( 'Pending', 'mantle' ),
				Post_Status::FAILED->value => esc_html__( 'Failed', 'mantle' ),
				Post_Status::RUNNING->value => esc_html__( 'Running', 'mantle' ),
				default => esc_html__( 'Unknown', 'mantle' ),
			},
			/* phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped */
		);
		?>
	</h1>

	<hr class="wp-header-end">

	<section class="queue-job-container">
		<aside>
			<div>
				<?php if ( Post_Status::FAILED->value === $job->status ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $job->id ),
									'filter'   => false,
									'job'      => (int) $job->id,
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
				<?php if ( Post_Status::RUNNING->value !== $job->status ) : ?>
					<a
						href="
						<?php
						echo esc_url(
							add_query_arg(
								[
									'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $job->id ),
									'filter'   => false,
									'job'      => (int) $job->id,
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
				<code><?php echo esc_html( $worker_job->get_id() ); ?></code>
			</p>
			<h3><?php esc_html_e( 'Arguments', 'mantle' ); ?></h3>
			<code>
				<?php echo wp_json_encode( is_object( $worker_job->get_job() ) ? get_object_vars( $worker_job->get_job() ) : '' ); ?>
			</code>
			<h3><?php esc_html_e( 'Queue', 'mantle' ); ?></h3>
			<p>
				<code><?php echo esc_html( $job->get_queue() ); ?></code>
			</p>
			<h3><?php esc_html_e( 'Lock Status', 'mantle' ); ?></h3>
			<p>
				<?php if ( $job->is_locked() ) : ?>
					<?php
					printf(
						/* translators: %s: The time the job is locked until. */
						esc_html__( 'Locked until %s', 'mantle' ),
						esc_html( Carbon::createFromTimestampUTC( $job->get_lock_until() )->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
					);
					?>
				<?php else : ?>
					<?php esc_html_e( 'Unlocked', 'mantle' ); ?>
				<?php endif; ?>
			</p>
		</aside>

		<section>
			<h3><?php esc_html_e( 'Job Log', 'mantle' ); ?></h3>
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
