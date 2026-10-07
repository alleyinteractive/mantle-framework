<?php
/**
 * Load_Method enum file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Contracts\Assets;

/**
 * Asset Load Methods
 */
enum Load_Method: string {
	case SYNC = 'sync';

	case ASYNC = 'async';

	case DEFER = 'defer';

	/**
	 * @deprecated Removed in wp-asset-manager 2.0, where it behaves as {@see Load_Method::ASYNC}.
	 */
	case ASYNC_DEFER = 'async-defer';
}
