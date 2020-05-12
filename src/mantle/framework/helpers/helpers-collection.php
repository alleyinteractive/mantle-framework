<?php
/**
 * Collection helper functions
 *
 * @package Mantle
 */

namespace Mantle\Framework\Helpers;

use Mantle\Framework\Collection\Collection;

function collect( $value = null ): Collection {
	return new Collection( $value );
}
