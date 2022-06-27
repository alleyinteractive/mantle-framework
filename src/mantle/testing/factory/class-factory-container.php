<?php
/**
 * Factory_Container class file.
 *
 * @package Mantle
 */

namespace Mantle\Testing\Factory;

use Mantle\Contracts\Container;

/**
 * Collect all the Unit Test Factories for IDE Support
 *
 * Allows IDEs to type-hint the individual factories and their child methods.
 */
class Factory_Container {
	/**
	 * Attachment Factory
	 *
	 * @var Attachment_Factory<\WP_Post|\Mantle\Database\Model\Attachment>
	 */
	public $attachment;

	/**
	 * Blog Factory
	 *
	 * @var Blog_Factory<\WP_Site|\Mantle\Database\Model\Site>
	 */
	public $blog;

	/**
	 * Category Factory
	 *
	 * @var Term_Factory<\WP_Term|\Mantle\Database\Model\Term>
	 */
	public $category;

	/**
	 * Comment Factory
	 *
	 * @var Comment_Factory<\WP_Comment>
	 */
	public $comment;

	/**
	 * Network Factory
	 *
	 * @var Network_Factory<\WP_Network>
	 */
	public $network;

	/**
	 * Page Factory
	 *
	 * @var Post_Factory<\WP_Post|\Mantle\Database\Model\Post>
	 */
	public $page;

	/**
	 * Post Factory
	 *
	 * @var Post_Factory<\WP_Post|\Mantle\Database\Model\Post>
	 */
	public $post;

	/**
	 * Tag Factory
	 *
	 * @var Term_Factory<\WP_Term|\Mantle\Database\Model\Term>
	 */
	public $tag;

	/**
	 * Term Factory
	 *
	 * @var Term_Factory<\WP_Term|\Mantle\Database\Model\Term>
	 */
	public $term;

	/**
	 * User Factory
	 *
	 * @var User_Factory<\WP_User|\Mantle\Database\Model\User>
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
