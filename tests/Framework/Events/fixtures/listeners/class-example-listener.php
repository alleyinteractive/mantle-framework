<?php
namespace Mantle\Tests\Framework\Events\Fixtures\Listeners;

use Mantle\Support\Attributes\Action;
use Mantle\Tests\Framework\Events\Fixtures\Events\Event_One;
use Mantle\Tests\Framework\Events\Fixtures\Events\Event_Two;
use WP_Query;

class Example_Listener {
	public function handle( Event_One $event ): void {
		// ...
	}

	public function handle_event_one( Event_One $event ): void {
		// ...
	}

	public function handle_event_two( Event_Two $event ): void {
		// ...
	}

	public function handle_event_two_at_20( Event_Two $event ): void {
		// ...
	}

	public function on_wp_loaded( WP_Query $query ): void {
		// ...
	}

	public function on_pre_get_posts_at_20( WP_Query $query ): void {
		// ...
	}

	#[Action('attribute-event')]
	public function handle_attribute_string_callback( $event ): void {
		// ...

	}

	#[Action('attribute-event', 20)]
	public function handle_attribute_string_callback_priority( $event ): void {
		// ...

	}

	#[Action(Event_One::class)]
	public function handle_attribute_event_one( Event_One $event ): void {
		// ...

	}
}
