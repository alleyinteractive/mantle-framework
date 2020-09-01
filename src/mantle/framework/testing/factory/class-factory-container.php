<?php
/**
 * Factory_Container class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Testing\Factory;

use Mantle\Framework\Contracts\Container;

/**
 * Collect all the Unit Test Factories for IDE Support
 *
 * Allows IDEs to type-hint the individual factories and their child methods.
 */
class Factory_Container {
	/**
	 * Blog Factory
	 *
	 * @var Blog_Factory
	 */
	public $blog;

	/**
	 * Category Factory
	 *
	 * @var Term_Factory
	 */
	public $category;

	/**
	 * Post Factory
	 *
	 * @var Post_Factory
	 */
	public $post;

	/**
	 * Tag Factory
	 *
	 * @var Term_Factory
	 */
	public $tag;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->blog     = $container->make( Blog_Factory::class );
		$this->category = $container->make( Term_Factory::class, [ 'taxonomy' => 'category' ] );
		$this->post     = $container->make( Post_Factory::class );
		$this->tag      = $container->make( Term_Factory::class, [ 'taxonomy' => 'post_tag' ] );
	}
}
