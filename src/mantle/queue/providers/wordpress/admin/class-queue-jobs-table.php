<?php
/**
 * Queue_Jobs_Table class file
 *
 * @package Mantle
 */

namespace Mantle\Queue\Providers\WordPress\Admin;

use Carbon\Carbon;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Providers\WordPress\Queue_Job;
use Mantle\Queue\Providers\WordPress\Queue_Worker_Job;
use WP_List_Table;

use function Mantle\Support\Helpers\str;

/**
 * Queue Jobs Table
 *
 * @todo Abstract this to make it easier to use with other queue providers.
 * @todo Add counts to views.
 */
class Queue_Jobs_Table extends WP_List_Table {
	/**
	 * Number of items per page.
	 *
	 * @var int
	 */
	public int $per_page = 50;

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
			'job'       => __( 'Job', 'mantle' ),
			'arguments' => __( 'Arguments', 'mantle' ),
			'queue'     => __( 'Queue', 'mantle' ),
			'date'      => __( 'Scheduled', 'mantle' ),
			'status'    => __( 'Status', 'mantle' ),
		];
	}

	/**
	 * Collect the views for the table.
	 *
	 * @return array
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

		foreach ( Post_Status::cases() as $status ) {
			$links[] = [
				'current' => $status->value === $current,
				'label'   => (string) str( $status->name )->title(),
				'url'     => add_query_arg( 'filter', $status->value ),
			];
		}

		return $this->get_views_links( $links );
	}

	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		/**
		 * Provider instance.
		 *
		 * @var \Mantle\Queue\Providers\WordPress\Provider
		 */
		$queue = app( 'queue' )->get_provider( 'wordpress' );

		$this->_column_headers = [ $this->get_columns(), [], [] ];

		$statuses = array_column( Post_Status::cases(), 'value' );

		$active_status_filter = sanitize_text_field( wp_unslash( $_GET['filter'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		// Validate that the status filter is valid.
		if ( ! empty( $active_status_filter ) && ! in_array( $active_status_filter, $statuses, true ) ) {
			$active_status_filter = '';
		}

		$active_queue_filter = sanitize_text_field( wp_unslash( $_GET['queue'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page                = (int) ( $_GET['paged'] ?? 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$query = Queue_Job::query()
			->orderBy( 'date', 'asc' )
			// Allow the query to be filtered by status.
			->when(
				! empty( $active_status_filter ),
				fn ( $query ) => $query->where( 'post_status', $active_status_filter ),
				fn ( $query ) => $query->where( 'post_status', $statuses ),
			)
			// Allow the query to be filtered by queue.
			->when(
				! empty( $active_queue_filter ),
				fn ( Post_Query_Builder $query ) => $query->whereTerm(
					Provider::get_queue_term_id( $active_queue_filter, false ),
					Provider::OBJECT_NAME,
				),
			)
			->for_page( $page, $this->per_page );

		// TODO: Refactor with found_posts later.
		$this->items = $query->get()->map(
			function ( Queue_Job $model ) {
				$worker = new Queue_Worker_Job( $model );

				$job = $worker->get_job();

				// Retrieve the properties from the job.
				if ( is_object( $job ) ) {
					$arguments = get_object_vars( $job );
				}

				return [
					'id'        => $model->ID,
					'job'       => $worker->get_id(),
					'arguments' => $arguments ?? '',
					'queue'     => $model->get_queue(),
					'date'      => $model->date,
					'status'    => $model->status,
				];
			}
		)->all();

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
	 * @param array $item The current item.
	 */
	public function column_job( $item ): void {
		$actions = [
			sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				esc_url(
					add_query_arg(
						[
							'job'    => (int) $item['id'],
							'filter' => false,
						]
					)
				),
				esc_attr__( 'View details about this job', 'mantle' ),
				esc_html__( 'View', 'mantle' ),
			),
			Post_Status::FAILED->value === $item['status']
				? sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					esc_url(
						add_query_arg(
							[
								'_wpnonce' => wp_create_nonce( 'retry-job_' . $item['id'] ),
								'filter'   => false,
								'job'      => (int) $item['id'],
								'retry'    => true,
							]
						)
					),
					esc_attr__( 'Retry this job', 'mantle' ),
					esc_html__( 'Retry', 'mantle' ),
				)
				: null,
			Post_Status::RUNNING->value !== $item['status']
				? sprintf(
					'<span class="trash"><a href="%s" aria-label="%s">%s</a></span>',
					esc_url(
						add_query_arg(
							[
								'_wpnonce' => wp_create_nonce( 'delete-job_' . $item['id'] ),
								'filter'   => false,
								'job'      => (int) $item['id'],
								'delete'   => true,
							]
						)
					),
					esc_attr__( 'Delete this job', 'mantle' ),
					esc_html__( 'Delete', 'mantle' ),
				)
				: null,
		];

		printf(
			'<div><code>%s</code></div><div class="row-actions">%s</div>',
			esc_html( $item['job'] ),
			implode( ' | ', array_filter( $actions ) ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	/**
	 * Display the arguments column.
	 *
	 * @param array $item The current item.
	 */
	public function column_arguments( $item ): void {
		echo '<code>' . wp_json_encode( $item['arguments'] ) . '</code>';
	}

	/**
	 * Display the queue column.
	 *
	 * @param array $item The current item.
	 */
	public function column_queue( $item ): void {
		printf(
			'<a href="%s">%s</a>',
			esc_url( add_query_arg( 'queue', $item['queue'] ) ),
			esc_html( $item['queue'] ),
		);
	}

	/**
	 * Display the date column.
	 *
	 * @param array $item The current item.
	 */
	public function column_date( $item ): void {
		$time = Carbon::parse( $item['date'], wp_timezone() );

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
	 * @param array $item The current item.
	 */
	public function column_status( $item ): void {
		switch ( $item['status'] ) {
			case Post_Status::PENDING->value:
				echo '<span class="dashicons dashicons-clock"></span>' . esc_html__( 'Pending', 'mantle' );
				break;

			case Post_Status::RUNNING->value:
				echo '<span class="dashicons dashicons-update"></span>' . esc_html__( 'Running', 'mantle' );
				break;

			case Post_Status::FAILED->value:
				echo '<span class="dashicons dashicons-no-alt"></span>' . esc_html__( 'Failed', 'mantle' );
				break;
		}
	}

	/**
	 * Gets the name of the default primary column.
	 */
	protected function get_default_primary_column_name() {
		return 'job';
	}
}
