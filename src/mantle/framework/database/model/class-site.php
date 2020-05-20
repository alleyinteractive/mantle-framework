<?php
/**
 * Site class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Database\Model;

use Mantle\Framework\Contracts;
use Mantle\Framework\Helpers;

/**
 * Site Model
 */
class Site extends Model implements Contracts\Database\Core_Object {
	/**
	 * Attributes for the model from the object
	 *
	 * @var array
	 */
	protected static $aliases = [
		'id'   => 'blog_id',
		'name' => 'title',
		'slug' => 'path',
	];

	/**
	 * Attributes that are guarded.
	 *
	 * @var array
	 */
	protected $guarded_attributes = [
		'site_ID',
	];

	/**
	 * Constructor.
	 *
	 * @param mixed $object Model object.
	 */
	public function __construct( $object ) {
		$this->attributes = (array) $object;
	}

	/**
	 * Find a model by Object ID.
	 *
	 * @param \WP_Site|string|int $object Site to retrieve.
	 * @return Site|null
	 */
	public static function find( $object ) {
		$site = Helpers\get_site_object( $object );
		return $site ? new static( $site ) : null;
	}

	/**
	 * Getter for Object ID.
	 *
	 * @return int
	 */
	public function id(): int {
		return (int) $this->get( 'id' );
	}

	/**
	 * Getter for Object Name.
	 *
	 * @return string
	 */
	public function name(): string {
		return (string) \get_blog_option( $this->id(), 'blogname' );
	}

	/**
	 * Getter for Object Slug.
	 *
	 * @return string
	 */
	public function slug(): string {
		return (string) $this->get( 'slug' );
	}

	/**
	 * Getter for Parent Object (if any)
	 *
	 * @return Contracts\Database\Core_Object|null
	 */
	public function parent(): ?Contracts\Database\Core_Object {
		return null;
	}

	/**
	 * Getter for Object Description
	 *
	 * @return string
	 */
	public function description(): string {
		return (string) $this->get( 'description' );
	}

	/**
	 * Getter for the Object Permalink
	 *
	 * @return string|null
	 */
	public function permalink(): ?string {
		return (string) \get_home_url( $this->id() );
	}
}
