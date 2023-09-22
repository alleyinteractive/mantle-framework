<?php
namespace Mantle\Queue\Admin;

use Carbon\Carbon;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Queue_Job;
use Mantle\Queue\Providers\WordPress\Queue_Worker_Job;
use WP_List_Table;

/**
 * Queue Jobs Table
 *
 * @todo Abstract this to make it easier to use with other queue providers.
 */
class Queue_Jobs_Table extends WP_List_Table {
	public function __construct() {
		parent::__construct( [
			'plural'   => __( 'Jobs', 'mantle' ),
			'singular' => __( 'Job', 'mantle' ),
		] );

		// dd('manage_' . $this->screen->id . '_columns');

	}

	// public function display() {
	// 	add_filter( 'manage_' . $this->screen->id . '_columns', [ $this, 'get_columns' ] );

	// 	parent::display();
	// }
	// function __construct() {
	// 	if ( ! empty( $_REQUEST['s'] ) ) {
	// 		$this->is_search = true;
	// 	}

	// 	parent::__construct( array(
	// 		'plural' => __( 'Co-Authors', 'co-authors-plus' ),
	// 		'singular' => __( 'Co-Author', 'co-authors-plus' ),
	// 	) );
	// 	}
	/**
	 * Gets the list of columns.
	 *
	 * @return string[] Array of column titles keyed by their column name.
	 */
	public function get_columns() {
		return [
			'job'    => __( 'Job', 'mantle' ),
			'date'   => __( 'Scheduled', 'mantle' ),
			'status' => __( 'Status', 'mantle' ),
		];
	}

	/**
	 * @global array $totals
	 * @global string $status
	 * @return array
	 */
	protected function get_views() {
		$links = [
			[
				'id'    => 'all',
				'title' => __( 'All', 'mantle' ),
				'count' => 0,
			],
		];

		dump($this->get_views_links( $links ));
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

		// $this->filters = [
		// 	'all' => 'All',
		// ];

		// $this->active_filter = 'all';

		// TODO: Apply filters
		// TODO: Apply pagination.
		$this->items = $queue
			->query()
			->take( 50 )
			->get()
			->map(
				function ( Queue_Job $model ): array {
					$job = new Queue_Worker_Job( $model );

					return [
						'job'    => $job->get_id(),
						'date'   => $model->date,
						'status' => $model->status,
					];
				}
			)
			->all();

		// $this->items = [
		// 	[
		// 		'job' => 'Test Job',
		// 		'created' => '2021-01-01 00:00:00',
		// 		'status' => 'pending',
		// 	],
		// 	[
		// 		'job' => 'Test Job',
		// 		'created' => '2021-01-01 00:00:00',
		// 		'status' => 'pending',
		// 	],
		// 	[
		// 		'job' => 'Test Job',
		// 		'created' => '2021-01-01 00:00:00',
		// 		'status' => 'pending',
		// 	],
		// ];
	}

	public function column_job( $item ) {
		echo esc_html( $item['job'] );
	}

	public function column_date( $item ) {
		$time = Carbon::parse( $item['date'], wp_timezone() );

		printf(
			'<span title="%1$s"><time datetime="%2$s">%3$s</time></span>',
			esc_attr( $time->format( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_attr( $time->format( 'c' ) ),
			esc_html( $time->diffForHumans() ),
		);
	}

	public function column_status( $item ) {
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
	 * Generates custom table navigation to prevent conflicting nonces.
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @param array $item The current item.
	 */
	public function single_row( $item ) {
		echo '<tr>';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Gets the name of the default primary column.
	 */
	protected function get_default_primary_column_name() {
		return 'job';
	}
}
