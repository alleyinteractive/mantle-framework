<?php
/**
 * Core_Object interface file.
 *
 * @package Mantle
 */

namespace Mantle\Contracts\Database;

/**
 * Provides a way to normalize interacting with assorted WordPress objects
 * which have different properties. Allows for a uniform experience when
 * retrieving/updating object data in posts, terms, etc.
 */
interface Core_Object {
	/**
	 * Getter for Object ID
	 *
	 * @return int
	 */
	public function id(): int;

	/**
	 * Getter for Object Name
	 *
	 * @return string
	 */
	public function name(): string;

	/**
	 * Getter for Object Slug
	 *
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Getter for Object Description
	 *
	 * @return string
	 */
	public function description(): string;

	/**
	 * Getter for Parent Object (if any)
	 *
	 * @return Core_Object|null
	 */
	public function parent(): ?Core_Object;

	/**
	 * Getter for the Object Permalink
	 *
	 * @return string|null
	 */
	public function permalink(): ?string;

	/**
	 * Retrieve the core object for the underlying object.
	 *
	 * @return mixed
	 */
	public function core_object();
}
