<?php
/**
 * Permalink_Generator class file.
 *
 * @package Mantle
 */

namespace Mantle\Database\Model;

use Mantle\Contracts\Database\Core_Object;
use Mantle\Database\Model\Events\Permalink_Generated;

use function Mantle\Support\Helpers\event;

/**
 * Model Permalink Generator
 *
 * Generate a model's permalink using attributes and aliases from the model.
 */
class Permalink_Generator implements \Stringable {
	/**
	 * Attributes for the generator.
	 *
	 * @var string[]
	 */
	protected $attributes = [];

	/**
	 * Constructor.
	 *
	 * @param string     $route Route to generate for.
	 * @param Model|null $model Model to generator for, optional.
	 */
	public function __construct( protected ?string $route, protected ?Model $model = null ) {
	}

	/**
	 * Generate a new instance.
	 *
	 * @param string     $route Route to generate for.
	 * @param Model|null $model Model to generator for, optional.
	 */
	public static function create( string $route, ?Model $model = null ): Permalink_Generator {
		return new static( $route, $model );
	}

	/**
	 * Generate the permalink.
	 */
	public function permalink(): string {
		event( new Permalink_Generated( $this ) );

		$route = preg_replace_callback(
			'/({[A-Za-z0-9-_]*})/',
			function ( array $match ): string {
				$attribute = substr( $match[0], 1, strlen( $match[0] ) - 2 );

				return $this->get_attribute( $attribute );
			},
			(string) $this->route
		);

		return home_url( $route ?: '' );
	}

	/**
	 * Retrieve the model instance.
	 */
	public function get_model(): ?Model {
		return $this->model;
	}

	/**
	 * Retrieve the generator route.
	 */
	public function get_route(): ?string {
		return $this->route;
	}

	/**
	 * Set the attributes for the generator.
	 */
	protected function set_attributes(): void {
		if ( $this->model instanceof \Mantle\Database\Model\Model ) {
			foreach ( $this->model->get_attributes() as $attribute => $value ) {
				$this->set_attribute( $attribute, $value );
			}
		}
	}

	/**
	 * Get an attribute.
	 *
	 * @param string $attribute Attribute to get.
	 */
	public function get_attribute( string $attribute ): string {
		$value = $this->attributes[ $attribute ] ?? ( $this->model instanceof \Mantle\Database\Model\Model ? $this->model->get( $attribute ) : null );

		// Fallback to the model's slug when using the object name as an attribute.
		if ( empty( $value ) && $this->model instanceof Core_Object && $attribute === $this->model::get_object_name() ) {
			$value = $this->model->slug();
		}

		if ( ! $value && $this->model instanceof \Mantle\Database\Model\Model ) {
			$value = $this->model[ $attribute ] ?? null;
		}

		return (string) $value;
	}

	/**
	 * Set an attribute for the generator.
	 *
	 * @param string $attribute Attribute to set.
	 * @param string $value Value to set.
	 */
	public function set_attribute( string $attribute, string $value ): static {
		$this->attributes[ $attribute ] = $value;
		return $this;
	}

	/**
	 * Convert the class to string.
	 */
	public function __toString(): string {
		return $this->permalink();
	}
}
