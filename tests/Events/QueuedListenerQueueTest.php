<?php
namespace Mantle\Tests\Events;

use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Database\Model\Post;
use Mantle\Events\Call_Queued_Listener;
use Mantle\Queue\Console\Run_Command;
use Mantle\Queue\Providers\WordPress\Meta_Key;
use Mantle\Queue\Providers\WordPress\Post_Status;
use Mantle\Queue\Providers\WordPress\Provider;
use Mantle\Queue\Providers\WordPress\Queue_Record;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

#[Group( 'events' )]
#[Group( 'queue' )]
class QueuedListenerQueueTest extends FrameworkTestCase {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		Provider::register_data_types();

		$_SERVER['__queued_model_listener'] = [];
	}

	protected function tearDown(): void {
		unset( $_SERVER['__queued_model_listener'] );

		parent::tearDown();
	}

	public function test_queued_listener_runs_from_the_wordpress_queue_with_a_model_payload(): void {
		$post = static::factory()->post->as_models()->create_and_get( [ 'post_title' => 'Queued Listener' ] );

		$this->app['events']->listen( __FUNCTION__, Queued_Model_Listener::class );

		$this->assertSame( $post, $this->app['events']->dispatch( __FUNCTION__, $post, 'extra' ) );

		$this->assertSame( [], $_SERVER['__queued_model_listener'] );
		$this->assertSame( 1, $this->app['queue']->get_provider()->pending_count() );
		$this->assertJobQueued( new Call_Queued_Listener( Queued_Model_Listener::class, 'handle', [ $post, 'extra' ] ) );

		$job = Queue_Record::where( 'post_status', Post_Status::PENDING->value )->first()?->get_meta( Meta_Key::JOB->value, true );

		$this->assertInstanceOf( Call_Queued_Listener::class, $job );
		$this->assertSame( Queued_Model_Listener::class, $job->class );

		$command = new Run_Command();
		$command->set_container( $this->app );
		$command->run( new ArrayInput( [], $command->getDefinition() ), new NullOutput() );

		$this->assertSame( [ [ $post->id(), 'Queued Listener', 'extra' ] ], $_SERVER['__queued_model_listener'] );
		$this->assertSame( 0, $this->app['queue']->get_provider()->pending_count() );
	}
}

class Queued_Model_Listener implements Can_Queue {
	public function handle( Post $post, string $extra ): void {
		$_SERVER['__queued_model_listener'][] = [ $post->id(), $post->title, $extra ];
	}
}
