<?php
/**
 * Authentication_Error class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Auth;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Thrown on error authenticating.
 */
class Authentication_Error extends HttpException { }
