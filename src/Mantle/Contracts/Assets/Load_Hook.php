<?php
/**
 * Load_Hook enum file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Contracts\Assets;

/**
 * Asset Load Hooks
 */
enum Load_Hook: string {
	case HEADER = 'wp_head';

	case FOOTER = 'wp_footer';
}
