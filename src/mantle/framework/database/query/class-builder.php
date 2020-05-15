<?php
namespace Mantle\Framework\Database\Query;

use Mantle\Framework\Database\Model\Model;
use Mantle\Framework\Support\Str;

class Builder {
	/**
	 * @var string
	 */
	protected $model;
	protected $limit = 100;
	protected $wheres = [];

	public function __construct( string $model ) {
		$this->model = $model;
	}

	public static function create( string $model ) {
		return new static( $model );
	}

	public function where( string $attribute, $value ) {
		$aliases = [
			'post_name' => 'name',
		];

		if ( ! empty( $aliases[ $attribute ] ) ) {
			$attribute = $aliases[ $attribute ];
		}

		$this->wheres[ $attribute ] = $value;
		return $this;
	}

	public function dynamicWhere( $method, $args ) {
		$finder = substr( $method, 5 );

		$attribute = Str::snake( $finder );

		// Use the model's alias if one exist.
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		return $this->where( $attribute, ...$args );
	}

	public function take( int $limit ) {
		$this->limit = $limit;
		return $this;
	}

	public function first() {
		return $this->take( 1 )->get()[0] ?? null;
	}

	protected function build_args(): array {
		return array_merge(
			$this->wheres,
			[
				'post_type'      => $this->model::get_object_name(),
				'posts_per_page' => $this->limit,
				'fields'         => 'ids',
			]
		);
	}
	public function get(): array {
		$post_ids = \get_posts( $this->build_args() );

		if ( empty( $post_ids ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( [ $this->model, 'find' ], $post_ids )
			)
		);
	}

	public function __call( $method, $args ) {
		if ( Str::starts_with( $method, 'where' ) ) {
			return $this->dynamicWhere( $method, $args );
		}

		// exception
	}
}
