<?php
/* The brackets ensure that `?>` doesn't kill the trailing newline */
?>
[Child loaded: <?php echo esc_html( mantle_get_var( 'custom_var', 'successfully' ) ) ?>]
