<?php
namespace Example;

use Mantle\Support\Attributes\Action;
use Mantle\Support\Attributes\Filter;
use Mantle\Support\Traits\Hookable;

class Hookable_Example {
	use Hookable;

	public function on_init(): void {}

	public function on_init_at_20(): void {}

	public function action__init(): void {}

	public function filter__the_filter( $value ) {
		return $value;
	}

	#[Action( 'init', 5 )]
	public function attribute_action(): void {}

	#[Filter( 'the_filter' ), Action( 'init' )]
	public function attribute_filter(): void {}

	public function on_init_extra(): void {}

	protected function on_init_protected(): void {}
}

function on_init() {}
