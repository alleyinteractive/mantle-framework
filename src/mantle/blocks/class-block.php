<?php
/**
 * Abstract Block class
 *
 * @package Mantle
 */

namespace Mantle\Blocks;

use Mantle\Contracts\Block as Block_Contract;

abstract class Block implements Block_Contract {

	/**
	 * The name of the block.
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * The namespace of the block.
	 *
	 * @var string
	 */
	protected string $namespace = '';

	/**
	 * Post types that support this block.
	 *
	 * Defaults to [ 'all' ], which is all registered post types.
	 *
	 * @var string[]
	 */
	protected array $post_types = [ 'all' ];

	/**
	 * Whether the block is a dynamic block or not.
	 * Default is true.
	 *
	 * @var bool
	 */
	protected bool $is_dynamic = true;

	public function register() {

	}

	abstract public function render();

}
