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
	protected $order = 'DESC';
	protected $order_by = 'date';
	protected $meta_query = [];

	public function __construct( string $model ) {
		$this->model = $model;
	}

	public static function create( string $model ) {
		return new static( $model );
	}

	public function where( $attribute, $value = '' ) {
		if ( is_array( $attribute ) && empty( $value ) ) {
			foreach ( $attribute as $key => $value ) {
				$this->where( $key, $value );
			}

			return $this;
		}

		$aliases = [
			'post_name' => 'name',
		];

		if ( ! empty( $aliases[ $attribute ] ) ) {
			$attribute = $aliases[ $attribute ];
		}

		$this->wheres[ $attribute ] = $value;
		return $this;
	}

	public function whereMeta( $key, $value, string $compare = '=' ) {
		$this->meta_query[] = [
			'compare' => $compare,
			'key'     => $key,
			'value'   => $value,
		];
		return $this;
	}

	public function andWhereMeta( ...$args ) {
		$this->meta_query['relation'] = 'AND';
		return $this->whereMeta( ...$args );
	}

	public function orWhereMeta( ...$args ) {
		$this->meta_query['relation'] = 'OR';
		return $this->whereMeta( ...$args );
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

	public function whereIn( string $attribute, array $values ) {
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		switch ( $attribute ) {
			case 'ID':
				$query_attribute = 'post__in';
				break;

			case 'post_name':
				$query_attribute = 'post_name__in';
				break;

			case 'post_parent':
				$query_attribute = 'post_parent__in';
				break;

			default:
				$query_attribute = false;
		}

		if ( empty( $query_attribute ) ) {
			throw new Query_Exception( 'Unknown attribute for "whereIn": ' . $attribute );
		}

		return $this->where( $query_attribute, $values );
	}

	public function whereNotIn( string $attribute, array $values ) {
		if ( $this->model::has_attribute_alias( $attribute ) ) {
			$attribute = $this->model::get_attribute_alias( $attribute );
		}

		switch ( $attribute ) {
			case 'ID':
				$query_attribute = 'post__not_in';
				break;

			case 'post_name':
				$query_attribute = 'post_name__not_in';
				break;

			case 'post_parent':
				$query_attribute = 'post_parent__not_in';
				break;

			default:
				$query_attribute = false;
		}

		if ( empty( $query_attribute ) ) {
			throw new Query_Exception( 'Unknown attribute for "whereNotIn": ' . $attribute );
		}

		return $this->where( $query_attribute, $values );
	}

	public function orderBy( $attribute, string $direction = 'asc' ) {
		$this->order = strtoupper( $direction );
		$this->order_by = $attribute;
		return $this;
	}

	public function dynamicOrderBy( string $method, array $args ) {
		$attribute = Str::snake( substr( $method, 7 ) );

		$attribute = str_replace( '_in', '__in', $attribute );
		return $this->orderBy( $attribute, $args[0] ?? 'asc' );
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
			[
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'meta_query'          => $this->meta_query,
				'order'               => $this->order,
				'orderby'             => $this->order_by,
				'post_type'           => $this->model::get_object_name(),
				'posts_per_page'      => $this->limit,
				'suppress_filters'    => false,
			],
			$this->wheres,
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

		if ( Str::starts_with( $method, 'orderBy' ) ) {
			return $this->dynamicOrderBy( $method, $args );
		}

		// exception
	}
}
