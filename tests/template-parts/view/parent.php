<?php
/* The brackets ensure that `?>` doesn't kill the newline */
?>
[Parent loaded: <?php echo esc_html( mantle_get_var( 'custom_var', 'successfully' ) ) ?>]
<?php echo view( '_child', [ 'custom_var' => mantle_get_var( 'child_var', 'successfully' ) ] ); ?>
