<?php
/**
 * UnexpectedIncorrectUsageException class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Exceptions;

use ErrorException;

/**
 * Exception for unexpected _doing_it_wrong() calls.
 */
class UnexpectedIncorrectUsageException extends BacktraceException {}
