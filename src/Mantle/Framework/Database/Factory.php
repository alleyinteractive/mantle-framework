<?php
namespace Mantle\Framework\Database;
use Faker\Generator as Faker;
class Factory
{
    /**
     * The model definitions in the container.
     *
     * @var array
     */
    protected $definitions = [];

    /**
     * The Faker instance for the builder.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Create a new factory instance.
     *
     * @param  \Faker\Generator  $faker
     * @return void
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Define a class with a given short-name.
     *
     * @param  string  $class
     * @param  string  $name
     * @param  callable  $attributes
     * @return $this
     */
    public function defineAs(string $class, string $name, callable $attributes): self
    {
        return $this->define($class, $attributes, $name);
    }

    /**
     * Define a class with a given set of attributes.
     *
     * @param  string  $class
     * @param  callable  $attributes
     * @param  string  $name
     * @return $this
     */
    public function define(string $class, callable $attributes, string $name = 'default'): self
    {
        $this->definitions[$class][$name] = $attributes;
        return $this;
    }
}
