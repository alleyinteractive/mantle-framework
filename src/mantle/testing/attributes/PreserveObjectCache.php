<?php
/**
 * PreserveCacheBetweenRequests class file
 *
 * @package mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Preserve the object cache between testing HTTP requests.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class PreserveObjectCache {}
