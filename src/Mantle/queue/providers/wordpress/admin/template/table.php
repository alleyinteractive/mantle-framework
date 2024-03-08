<?php
/**
 * Render the admin template to display the queue.
 *
 * @package Mantle
 * @var \Mantle\Queue\Providers\WordPress\Admin\Queue_Jobs_Table $table The queue jobs table.
 */

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Mantle Queue', 'mantle' ); ?></h1>

	<hr class="wp-header-end">

	<?php $table->views(); ?>
	<?php $table->display(); ?>
</div>

<style type="text/css">
	table.wp-list-table tr.queue-item__queue_running {
		background: #FEF3C7;
	}

	table.wp-list-table tr.queue-item__queue_failed {
		background: #FED7D7;
	}

	tr.queue-item td {
		background: transparent
	}
</style>
