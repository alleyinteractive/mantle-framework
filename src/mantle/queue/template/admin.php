<?php
/**
 * Render the admin template to display the queue.
 *
 * @var \Mantle\Queue\Admin\Queue_Jobs_Table $table The queue jobs table.
 */
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Mantle Queue', 'mantle' ); ?></h1>

	<?php $table->views(); ?>
	<?php $table->display(); ?>
</div>
