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
	 * Attachment Factory
	 *
	 * @var Attachment_Factory
	 */
	public $attachment;

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
	 * Comment Factory
	 *
	 * @var Comment_Factory
	 */
	public $comment;

	/**
	 * Network Factory
	 *
	 * @var Network_Factory
	 */
	public $network;

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
	 * User Factory
	 *
	 * @var User_Factory
	 */
	public $user;

	/**
	 * Constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->attachment = $container->make( Attachment_Factory::class );
		$this->category   = $container->make( Term_Factory::class, [ 'taxonomy' => 'category' ] );
		$this->comment    = $container->make( Comment_Factory::class );
		$this->page       = $container->make( Post_Factory::class, [ 'post_type' => 'page' ] );
		$this->post       = $container->make( Post_Factory::class );
		$this->tag        = $container->make( Term_Factory::class, [ 'taxonomy' => 'post_tag' ] );
		$this->term       = $container->make( Term_Factory::class, [ 'taxonomy' => 'post_tag' ] );
		$this->user       = $container->make( User_Factory::class );

		if ( is_multisite() ) {
			$this->blog    = $container->make( Blog_Factory::class );
			$this->network = $container->make( Network_Factory::class );
		}
	}
}
