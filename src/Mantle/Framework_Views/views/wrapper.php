<?php
/**
 * Blade Template Wrapper
 *
 * @package Mantle
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<div class="wp-site-blocks">
		<?php
		/**
		 * Filter the header template.
		 *
		 * @param string $block Gutenberg block template.
		 */
		echo do_blocks( apply_filters( 'mantle_block_template_header', '<!-- wp:template-part {"slug":"header"} /-->' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Assumed to be sanitized.
		if ( isset( $response ) && $response instanceof \Symfony\Component\HttpFoundation\Response ) {
			echo $response->getContent(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Filter the footer template.
		 *
		 * @param string $block Gutenberg block template.
		 */
		echo do_blocks( apply_filters( 'mantle_block_template_footer', '<!-- wp:template-part {"slug":"footer"} /-->' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
