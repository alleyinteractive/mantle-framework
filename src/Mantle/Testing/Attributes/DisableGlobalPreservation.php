<?php
/**
 * DisableGlobalPreservation class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Attributes;

use Attribute;

/**
 * Disable global preservation for the test.
 *
 * @see \Mantle\Testing\Concerns\Preserves_Globals
 */
#[Attribute( Attribute::TARGET_CLASS )]
class DisableGlobalPreservation {}
