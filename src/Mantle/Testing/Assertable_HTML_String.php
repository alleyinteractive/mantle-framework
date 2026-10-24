<?php
/**
 * Assertable_HTML_String class file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use Mantle\Support\HTML;
use Mantle\Testing\Concerns\Element_Assertions;
use PHPUnit\Framework\Assert;

/**
 * HTML String
 *
 * Perform assertions against a HTML string.
 *
 * @deprecated Migrated to \Mantle\Support\HTML which has been enhanced to
 *             support assertions as well as HTML manipulation.
 */
class Assertable_HTML_String extends HTML {}
