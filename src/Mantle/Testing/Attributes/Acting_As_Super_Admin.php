<?php
/**
 * Acting_As_Super_Admin class file.
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Run a multisite test as a network super admin.
 *
 * Takes precedence over Acting_As. On single-site installations, the test is skipped.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class Acting_As_Super_Admin {}
