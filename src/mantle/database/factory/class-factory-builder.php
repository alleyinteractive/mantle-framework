<?php
/**
 * Factory class file.
 *
 * @package Mantle
 */

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamComment

namespace Mantle\Database\Factory;

use Faker\Generator as Faker;
use InvalidArgumentException;
use Mantle\Database\Model\Model;
use Mantle\Support\Collection;
use Mantle\Support\Traits\Macroable;
use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Class Factory_Builder
 */
class Factory_Builder {
	use Macroable;

	/**
	 * The model definitions in the container.
	 *
	 * @var array
	 */
	protected $definitions;

	/**
	 * The model being built.
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * The database connection on which the model instance should be persisted.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The model states.
	 *
	 * @var array
	 */
	protected $states;

	/**
	 * The model after making callbacks.
	 *
	 * @var array
	 */
	protected $after_making = [];

	/**
	 * The model after creating callbacks.
	 *
	 * @var array
	 */
	protected $after_creating = [];

	/**
	 * The states to apply.
	 *
	 * @var array
	 */
	protected $active_states = [];

	/**
	 * The Faker instance for the builder.
	 *
	 * @var Faker
	 */
	protected $faker;

	/**
	 * The number of models to build.
	 *
	 * @var int|null
	 */
	protected $amount = null;

	/**
	 * Create an new builder instance.
	 *
	 * @param string $class
	 * @param array  $definitions
	 * @param array  $states
	 * @param array  $after_making
	 * @param array  $after_creating
	 * @param Faker  $faker
	 *
	 * @return void
	 */
	public function __construct(
		$class, array $definitions, array $states,
		array $after_making, array $after_creating, Faker $faker
	) {
		$this->class          = $class;
		$this->faker          = $faker;
		$this->states         = $states;
		$this->definitions    = $definitions;
		$this->after_making   = $after_making;
		$this->after_creating = $after_creating;
	}

	/**
	 * Set the amount of models you wish to create / make.
	 *
	 * @param int $amount
	 *
	 * @return Factory_Builder
	 */
	public function times( $amount ) {
		$this->amount = $amount;

		return $this;
	}

	/**
	 * Set the state to be applied to the model.
	 *
	 * @param string $state
	 *
	 * @return $this
	 */
	public function state( $state ) {
		return $this->states( [ $state ] );
	}

	/**
	 * Set the states to be applied to the model.
	 *
	 * @param array|mixed $states
	 *
	 * @return $this
	 */
	public function states( $states ) {
		$this->active_states = is_array( $states ) ? $states : func_get_args();

		return $this;
	}

	/**
	 * Set the database connection on which the model instance should be persisted.
	 *
	 * @param string $name
	 *
	 * @return $this
	 */
	public function connection( $name ) {
		$this->connection = $name;

		return $this;
	}

	/**
	 * Create a model and persist it in the database if requested.
	 *
	 * @param array $attributes
	 *
	 * @return \Closure
	 */
	public function lazy( array $attributes = [] ) {
		return function () use ( $attributes ) {
			return $this->create( $attributes );
		};
	}

	/**
	 * Create a collection of models and persist them to the database.
	 *
	 * @param array $attributes
	 *
	 * @return Collection|Model|mixed
	 */
	public function create( array $attributes = [] ) {
		$results = $this->make( $attributes );

		if ( $results instanceof Model ) {
			$this->store( collect( [ $results ] ) );

			$this->call_after_creating( collect( [ $results ] ) );
		} else {
			$this->store( $results );

			$this->call_after_creating( $results );
		}

		return $results;
	}

	/**
	 * Create a collection of models and persist them to the database.
	 *
	 * @param iterable $records
	 *
	 * @return Collection|mixed
	 */
	public function create_many( iterable $records ) {
		return ( new $this->class() )->newCollection(
			array_map(
				function ( $attribute ) {
					return $this->create( $attribute );
				},
				$records
			)
		);
	}

	/**
	 * Set the connection name on the results and store them.
	 *
	 * @param Collection $results
	 *
	 * @return void
	 */
	protected function store( $results ) {
		$results->each(
			function ( $model ) {
				$model->save();
			}
		);
	}

	/**
	 * Create a collection of models.
	 *
	 * @param array $attributes
	 *
	 * @return Collection|Model|mixed
	 */
	public function make( array $attributes = [] ) {
		if ( null === $this->amount ) {
			return tap(
				$this->make_instance( $attributes ),
				function ( $instance ) {
					$this->call_after_making( collect( [ $instance ] ) );
				}
			);
		}

		if ( $this->amount < 1 ) {
			return ( new $this->class() )->newCollection();
		}

		$objects = array_map(
			function() use ( $attributes ) {
				return tap(
					$this->make_instance( $attributes ),
					function ( $instance ) {
						$this->call_after_making( collect( [ $instance ] ) );
					}
				);
			},
			range(
				1,
				$this->amount,
			)
		);

		$instances = new Collection( $objects );

		$this->call_after_making( $instances );

		return $instances;
	}

	/**
	 * Create an array of raw attribute arrays.
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 */
	public function raw( array $attributes = [] ) {
		if ( null === $this->amount ) {
			return $this->get_raw_attributes( $attributes );
		}

		if ( $this->amount < 1 ) {
			return [];
		}

		return array_map(
			function () use ( $attributes ) {
				return $this->get_raw_attributes( $attributes );
			},
			range(
				1,
				$this->amount
			)
		);
	}

	/**
	 * Get a raw attributes array for the model.
	 *
	 * @param array $attributes
	 *
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException Invalid argument.
	 */
	protected function get_raw_attributes( array $attributes = [] ) {
		if ( ! isset( $this->definitions[ $this->class ] ) ) {
			throw new InvalidArgumentException( "Unable to locate factory for [{$this->class}]." );
		}

		$definition = call_user_func(
			$this->definitions[ $this->class ],
			$this->faker,
			$attributes
		);

		return $this->expand_attributes(
			array_merge( $this->apply_states( $definition, $attributes ), $attributes )
		);
	}

	/**
	 * Make an instance of the model with the given attributes.
	 *
	 * @param array $attributes
	 *
	 * @return Model
	 */
	protected function make_instance( array $attributes = [] ) {
		return Model::unguarded(
			function () use ( $attributes ) {
				$instance = new $this->class(
					$this->get_raw_attributes( $attributes )
				);

				if ( isset( $this->connection ) ) {
					$instance->setConnection( $this->connection );
				}

				return $instance;
			}
		);
	}

	/**
	 * Apply the active states to the model definition array.
	 *
	 * @param array $definition
	 * @param array $attributes
	 *
	 * @return array
	 *
	 * @throws \InvalidArgumentException Invalid argument.
	 */
	protected function apply_states( array $definition, array $attributes = [] ) {
		foreach ( $this->active_states as $state ) {
			if ( ! isset( $this->states[ $this->class ][ $state ] ) ) {
				if ( $this->state_has_after_callback( $state ) ) {
					continue;
				}

				throw new InvalidArgumentException( "Unable to locate [{$state}] state for [{$this->class}]." );
			}

			$definition = array_merge(
				$definition,
				$this->state_attributes( $state, $attributes )
			);
		}

		return $definition;
	}

	/**
	 * Get the state attributes.
	 *
	 * @param string $state
	 * @param array  $attributes
	 *
	 * @return array
	 */
	protected function state_attributes( $state, array $attributes ) {
		$state_attributes = $this->states[ $this->class ][ $state ];

		if ( ! is_callable( $state_attributes ) ) {
			return $state_attributes;
		}

		return $state_attributes( $this->faker, $attributes );
	}

	/**
	 * Expand all attributes to their underlying values.
	 *
	 * @param array $attributes
	 *
	 * @return array
	 */
	protected function expand_attributes( array $attributes ) {
		foreach ( $attributes as &$attribute ) {
			if ( is_callable( $attribute ) && ! is_string( $attribute ) && ! is_array( $attribute ) ) {
				$attribute = $attribute( $attributes );
			}

			if ( $attribute instanceof static ) {
				$attribute = $attribute->create()->getKey();
			}

			if ( $attribute instanceof Model ) {
				$attribute = $attribute->getKey();
			}
		}

		return $attributes;
	}

	/**
	 * Run after making callbacks on a collection of models.
	 *
	 * @param Collection $models
	 *
	 * @return void
	 */
	public function call_after_making( $models ) {
		$this->call_after( $this->after_making, $models );
	}

	/**
	 * Run after creating callbacks on a collection of models.
	 *
	 * @param Collection $models
	 *
	 * @return void
	 */
	public function call_after_creating( $models ) {
		$this->call_after( $this->after_creating, $models );
	}

	/**
	 * Call after callbacks for each model and state.
	 *
	 * @param array      $after_callbacks
	 * @param Collection $models
	 *
	 * @return void
	 */
	protected function call_after( array $after_callbacks, $models ) {
		$states = array_merge( [ 'default' ], $this->active_states );

		$models->each(
			function ( $model ) use ( $states, $after_callbacks ) {
				foreach ( $states as $state ) {
					$this->call_after_callbacks( $after_callbacks, $model, $state );
				}
			}
		);
	}

	/**
	 * Call after callbacks for each model and state.
	 *
	 * @param array  $after_callbacks
	 * @param Model  $model
	 * @param string $state
	 *
	 * @return void
	 */
	protected function call_after_callbacks( array $after_callbacks, $model, $state ) {
		if ( ! isset( $after_callbacks[ $this->class ][ $state ] ) ) {
			return;
		}

		foreach ( $after_callbacks[ $this->class ][ $state ] as $callback ) {
			$callback( $model, $this->faker );
		}
	}

	/**
	 * Determine if the given state has an "after" callback.
	 *
	 * @param string $state
	 *
	 * @return bool
	 */
	protected function state_has_after_callback( $state ) {
		return isset( $this->after_making[ $this->class ][ $state ] ) || isset( $this->after_creating[ $this->class ][ $state ] );
	}
}
