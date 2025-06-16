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
 *
 * With Mantle 2.0 this will be the default behavior and this attribute will no
 * longer be necessary.
 */
#[Attribute( Attribute::TARGET_CLASS | Attribute::TARGET_METHOD )]
class PreserveObjectCache {}
