<?php
/**
 * Guarded_Attributes class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

/**
 * Guard Specific Attributes from being set.
 */
trait Guarded_Attributes {
	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [];

	/**
	 * Flag if the model is being guarded.
	 *
	 * @var bool
	 */
	protected $guarded = true;

	/**
	 * Indicates if all mass assignment is enabled.
	 *
	 * @var bool
	 */
	protected static $unguarded = false;
	/**
	 * Check if the attribute is guarded.
	 *
	 * @param string $attribute Attribute to check.
	 * @return bool
	 */
	protected function is_guarded( string $attribute ): bool {
		if ( ! $this->guarded ) {
			return false;
		}

		return in_array( $attribute, $this->guarded_attributes, true );
	}

	/**
	 * Check if the model is currently guarded.
	 *
	 * @param bool $guarded Flag if the model is guarded.
	 */
	protected function guard( bool $guarded ) {
		$this->guarded = $guarded;
	}

	/**
	 * Run the given callable while being unguarded.
	 *
	 * @param  callable  $callback
	 * @return mixed
	 */
	public static function unguarded(callable $callback)
	{
		if (static::$unguarded) {
			return $callback();
		}

		static::unguard();

		try {
			return $callback();
		} finally {
			static::reguard();
		}
	}

	/**
	 * Disable all mass assignable restrictions.
	 *
	 * @param  bool  $state
	 * @return void
	 */
	public static function unguard($state = true)
	{
		static::$unguarded = $state;
	}

	/**
	 * Enable the mass assignment restrictions.
	 *
	 * @return void
	 */
	public static function reguard()
	{
		static::$unguarded = false;
	}
}
