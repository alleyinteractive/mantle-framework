<?php
/**
 * Load_Method class file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Assets;

/**
 * Asset Load Methods
 */
class Load_Method {
	/**
	 * Synchronous load method.
	 *
	 * @var string
	 */
	public const SYNC = 'sync';

	/**
	 * Asynchronous load method.
	 *
	 * @var string
	 */
	public const ASYNC = 'async';

	/**
	 * Defer load method.
	 *
	 * @var string
	 */
	public const DEFER = 'defer';

	/**
	 * Asynchronous and Defer load method.
	 *
	 * @var string
	 */
	public const ASYNC_DEFER = 'async-defer';
}
