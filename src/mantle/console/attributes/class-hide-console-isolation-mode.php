<?php
/**
 * Hide_Console_Isolation_Mode class file
 *
 * @package Mantle
 */

namespace Mantle\Console\Attributes;

use Attribute;

/**
 * Attribute to automatically hide a command in console isolation mode.
 */
#[Attribute( Attribute::TARGET_CLASS )]
class Hide_Console_Isolation_Mode {}
