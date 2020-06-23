<?php
/* The brackets ensure that PHP doesn't kill the newline */
?>
[Parent loop post <?php echo esc_html( mantle_get_var( 'index' ) ) ?>: <?php the_ID() ?>]
<?php echo loop( mantle_get_var( 'child_query' ), '_post' ); ?>
