<?php
/**
 * Render the view for a single job.
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package Mantle
 * @var \Mantle\Queue\Providers\WordPress\Queue_Job $job The queue job.
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

	<div class="queue-job-container">
		<div>
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
		</div>

		<div>
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
							<?php if ( Carbon::now( wp_timezone() )->diffInDays( $time ) <= 1 ) : ?>
								<span title="<?php echo esc_attr( $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?>">
									<?php echo esc_html( $time->diffForHumans() ); ?>
								</span>
							<?php else : ?>
								<?php echo esc_html( $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?>
							<?php endif; ?>
						</p>

						<?php if ( ! empty( $entry['payload'] ) ) : ?>
							<pre><?php echo wp_json_encode( $entry['payload'], JSON_PRETTY_PRINT ); ?></pre>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</div>

<style type="text/css">
	/* Two column grid */
	.queue-job-container {
		display: grid;
		grid-template-columns: 1fr 1fr;
		grid-gap: 1rem;
	}
	.queue-job-container pre {
		background: #D4D4D4;
		padding: 1rem;
		border-radius: 0.25rem;
	}
</style>
