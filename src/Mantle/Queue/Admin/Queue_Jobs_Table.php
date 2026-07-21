<?php
/**
 * Queue_Jobs_Table class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Admin;

use Carbon\Carbon;
use Mantle\Database\Query\Collection;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;
use WP_List_Table;

use function Mantle\Support\Helpers\str;

/**
 * Queue Jobs Table
 *
 * Display the jobs in a table with filters to view the jobs by status/queue.
 */
class Queue_Jobs_Table extends WP_List_Table {
	/**
	 * Number of items per page.
	 */
	public int $per_page = 50;

	/**
	 * Collection of items.
	 *
	 * @var Collection<int, Database_Job_Record>
	 */
	public $items;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'plural'   => __( 'Jobs', 'mantle' ),
				'singular' => __( 'Job', 'mantle' ),
			]
		);
	}

	/**
	 * Gets the list of columns.
	 *
	 * @return string[] Array of column titles keyed by their column name.
	 */
	public function get_columns() {
		return [
			'job'            => __( 'Job', 'mantle' ),
			'arguments'      => __( 'Arguments', 'mantle' ),
			'queue'          => __( 'Queue', 'mantle' ),
			'scheduled_date' => __( 'Scheduled Date', 'mantle' ),
			'updated_date'   => __( 'Updated Date', 'mantle' ),
			'status'         => __( 'Status', 'mantle' ),
		];
	}

	/**
	 * Collect the views for the table.
	 *
	 * @return array<mixed>
	 */
	protected function get_views() {
		$current = sanitize_text_field( wp_unslash( $_GET['filter'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$links = [
			[
				'current' => empty( $current ),
				'label'   => __( 'All', 'mantle' ),
				'url'     => add_query_arg( 'filter', '' ),
			],
		];

		foreach ( Status::cases() as $status ) {
			$count = $this->get_status_count( $status );

			$links[] = [
				'current' => $status->value === $current,
				'label'   => str( $status->name )
					->title()
					->when(
						$count > 0,
						fn ( $str ) => $str->append(
							sprintf(
								' <span class="count">(%d)</span>',
								esc_html( number_format_i18n( $count ) ),
							),
						),
					)
					->toString(),
				'url'     => add_query_arg(
					[
						'action' => null,
						'job'    => null,
						'filter' => $status->value,
					]
				),
			];
		}

		return $this->get_views_links( $links );
	}

	/**
	 * Retrieve the count of items on a specific status.
	 *
	 * @todo Add caching to this method.
	 *
	 * @param Status $status The status to retrieve the count for.
	 */
	protected function get_status_count( Status $status ): int {
		return Database_Job_Record::query()
			->where( 'status', $status->value )
			->count();
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items(): void {
		$this->_column_headers = [ $this->get_columns(), [], [] ];

		$statuses = array_column( Status::cases(), 'value' );

		$active_status_filter = sanitize_text_field( wp_unslash( $_GET['filter'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Validate that the status filter is valid.
		if ( ! empty( $active_status_filter ) && ! in_array( $active_status_filter, $statuses, true ) ) {
			$active_status_filter = '';
		}

		$active_queue_filter = sanitize_text_field( wp_unslash( $_GET['queue'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page                = (int) ( $_GET['paged'] ?? 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$query = Database_Job_Record::query()
			->orderBy( 'created_date_gmt', 'asc' )
			// Allow the query to be filtered by status.
			->when(
				! empty( $active_status_filter ),
				fn ( $query ) => $query->where( 'status', $active_status_filter ),
				fn ( $query ) => $query->where( 'status', $statuses ),
			)
			// Allow the query to be filtered by queue.
			->when(
				! empty( $active_queue_filter ),
				fn ( Post_Query_Builder $query ) => $query->where( 'queue', $active_queue_filter ),
			)
			->for_page( $page, $this->per_page );

		$this->items = $query->get();

		$this->set_pagination_args(
			[
				'total_items' => $query->get_found_rows(),
				'per_page'    => $this->per_page,
			]
		);
	}

	/**
	 * Display the job column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_job( Database_Job_Record $item ): void {
		$actions = [
			sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'_wpnonce' => false,
							'action'   => false,
							'filter'   => false,
							'job'      => (int) $item->id,
						]
					)
				),
				esc_attr__( 'View details about this job', 'mantle' ),
				esc_html__( 'View', 'mantle' ),
			),
			Status::PENDING->value === $item->status
				? sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url(
						add_query_arg(
							[
								'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $item->id ),
								'job'      => (int) $item->id,
								'action'   => 'run',
							]
						)
					),
					esc_attr__( 'Run this job', 'mantle' ),
					esc_html__( 'Run', 'mantle' ),
				)
				: null,
			Status::FAILED->value === $item->status
				? sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url(
						add_query_arg(
							[
								'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $item->id ),
								'job'      => (int) $item->id,
								'action'   => 'retry',
							]
						)
					),
					esc_attr__( 'Retry this job', 'mantle' ),
					esc_html__( 'Retry', 'mantle' ),
				)
				: null,
			Status::RUNNING->value !== $item->status
				? sprintf(
					'<span class="trash"><a href="%s" aria-label="%s" onclick="%s">%s</a></span>',
					esc_url(
						add_query_arg(
							[
								'_wpnonce' => wp_create_nonce( 'queue-job-action-' . $item->id ),
								'job'      => (int) $item->id,
								'action'   => 'delete',
							]
						)
					),
					esc_attr__( 'Delete this job', 'mantle' ),
					"return confirm('" . esc_attr__( 'Are you sure you want to delete this job?', 'mantle' ) . "');",
					esc_html__( 'Delete', 'mantle' ),
				)
				: null,
		];

		printf(
			'<div><code>%s</code></div><div class="row-actions">%s</div>',
			esc_html( $item->job()->get_id() ),
			implode( ' | ', array_filter( $actions ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Display the arguments column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_arguments( Database_Job_Record $item ): void {
		$job = $item->job()->get_job();
		echo '<code>' . wp_json_encode( is_object( $job ) ? get_object_vars( $job ) : '' ) . '</code>';
	}

	/**
	 * Display the queue column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_queue( Database_Job_Record $item ): void {
		printf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'queue', $item->queue ) ),
			esc_html( $item->queue ),
		);
	}

	/**
	 * Display the scheduled date column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_scheduled_date( Database_Job_Record $item ): void {
		$time = Carbon::parse( $item->scheduled_date_gmt, 'UTC' )->setTimezone( wp_timezone() );

		$date = $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		printf(
			'<span title="%1$s"><time datetime="%2$s">%3$s</time></span>',
			esc_attr( $date ),
			esc_attr( $time->format( 'c' ) ),
			esc_html( $date ),
		);
	}

	/**
	 * Display the updated date column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_updated_date( Database_Job_Record $item ): void {
		$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		$last_attempt_gmt = $item->last_attempt_gmt
			? Carbon::parse( $item->last_attempt_gmt )->setTimezone( wp_timezone() )
			: null;
		$available_at_gmt = Carbon::parse( $item->available_at_gmt )->setTimezone( wp_timezone() );

		if ( Status::COMPLETED->value === $item->status ) {
			if ( ! $last_attempt_gmt instanceof \Carbon\Carbon ) {
				return;
			}

			printf(
				/* translators: %s is the date of the last attempt. */
				esc_html__( 'Completed on %s', 'mantle' ),
				esc_html( date_i18n( $format, $last_attempt_gmt->getTimestamp() ) ),
			);

			return;
		}

		echo '<ul style="list-style: disc; margin: 0;">';

		if ( $last_attempt_gmt instanceof \Carbon\Carbon ) {
			printf(
				/* translators: %1$s is the date of the last attempt, %2$s is the human-readable time difference. */
				'<li>' . esc_html__( 'Last attempted on %1$s', 'mantle' ) . '</li>',
				esc_html( date_i18n( $format, $last_attempt_gmt->getTimestamp() ) ),
			);
		}

		if ( Status::FAILED->value !== $item->status ) {
			printf(
				/* translators: %1$s is the date, %2$s is the human-readable time difference. */
				'<li>' . esc_html__( 'Available at %1$s (%2$s)', 'mantle' ) . '</li>',
				esc_html( date_i18n( $format, $available_at_gmt->getTimestamp() ) ),
				esc_html( $available_at_gmt->diffForHumans() ),
			);
		}

		echo '</ul>';
	}

	/**
	 * Display the available column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_available( Database_Job_Record $item ): void {
		$time = Carbon::parse( $item->available_at_gmt, 'UTC' )->setTimezone( wp_timezone() );

		printf(
			'<span title="%1$s"><time datetime="%2$s">%3$s</time></span>',
			esc_attr( $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_attr( $time->format( 'c' ) ),
			esc_html( $time->diffForHumans() ),
		);
	}

	/**
	 * Display the status column.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function column_status( Database_Job_Record $item ): void {
		if ( $item->is_locked() ) {
			echo '<span class="dashicons dashicons-lock"></span>' . esc_html__( 'Locked', 'mantle' );
			return;
		}

		echo match ( Status::from( $item->status ) ) {
			Status::PENDING => '<span class="dashicons dashicons-clock"></span>' . esc_html__( 'Pending', 'mantle' ),
			Status::RUNNING => '<span class="dashicons dashicons-update"></span>' . esc_html__( 'Running', 'mantle' ),
			Status::FAILED => '<span class="dashicons dashicons-no-alt"></span>' . esc_html__( 'Failed', 'mantle' ),
			Status::COMPLETED => '<span class="dashicons dashicons-yes"></span>' . esc_html__( 'Completed', 'mantle' ),
		};
	}

	/**
	 * Gets the name of the default primary column.
	 */
	protected function get_default_primary_column_name() {
		return 'job';
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @param Database_Job_Record $item The current item.
	 */
	public function single_row( $item ): void {
		assert( $item instanceof Database_Job_Record );

		printf( '<tr class="%s">', esc_attr( 'queue-item queue-item__' . $item->status ) );
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Check if the table has items.
	 *
	 * @return bool True if the table has items, false otherwise.
	 */
	public function has_items() {
		return ! $this->items->is_empty();
	}
}
