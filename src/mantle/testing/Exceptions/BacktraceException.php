<?php
/**
 * BacktraceException class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing\Exceptions;

use ErrorException;
use Spatie\Backtrace\Frame;

/**
 * Exception for backtrace-based errors.
 */
abstract class BacktraceException extends ErrorException {
	/**
	 * Create an instance from a message and frame.
	 *
	 * @param string $message  The exception message.
	 * @param Frame  $frame    The backtrace frame.
	 * @param int    $code     The exception code.
	 * @param int    $severity The severity level.
	 */
	public static function create( string $message, Frame $frame, int $code = E_USER_ERROR, int $severity = E_USER_ERROR ): static {
		return new static(
			message: $message,
			code: $code,
			severity: $severity,
			filename: $frame->file,
			line: $frame->lineNumber,
		);
	}
}
