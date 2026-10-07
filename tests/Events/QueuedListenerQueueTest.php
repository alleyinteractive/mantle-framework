<?php
namespace Mantle\Tests\Events;

use Mantle\Contracts\Queue\Can_Queue;
use Mantle\Database\Model\Post;
use Mantle\Events\Call_Queued_Listener;
use Mantle\Queue\Database_Scheduler;
use Mantle\Queue\Jobs\Database_Job_Record;
use Mantle\Queue\Jobs\Status;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group( 'events' )]
#[Group( 'queue' )]
class QueuedListenerQueueTest extends FrameworkTestCase {
	use Refresh_Database;

	protected function setUp(): void {
		parent::setUp();

		$_SERVER['__queued_model_listener'] = [];
	}

	protected function tearDown(): void {
		unset( $_SERVER['__queued_model_listener'] );

		parent::tearDown();
	}

	public function test_queued_listener_runs_from_the_database_queue_with_a_model_payload(): void {
		$post = static::factory()->post->as_models()->create_and_get( [ 'post_title' => 'Queued Listener' ] );

		$this->app['events']->listen( __FUNCTION__, Queued_Model_Listener::class );

		$this->assertSame( $post, $this->app['events']->dispatch( __FUNCTION__, $post, 'extra' ) );

		$this->assertSame( [], $_SERVER['__queued_model_listener'] );
		$this->assertSame( 1, $this->app['queue']->get_provider()->pending_size( 'listeners' ) );
		$expected        = new Call_Queued_Listener( Queued_Model_Listener::class, 'handle', [ $post, 'extra' ] );
		$expected->queue = 'listeners';

		$this->assertJobQueued( $expected, queue: 'listeners' );

		$this->app->make( Database_Scheduler::class )->schedule_on_shutdown();

		$this->assertGreaterThan( 0, Database_Scheduler::get_scheduled_count( 'listeners' ) );

		$job = Database_Job_Record::query()->where( 'status', Status::PENDING->value )->first()?->job()->get_job();

		$this->assertInstanceOf( Call_Queued_Listener::class, $job );
		$this->assertSame( Queued_Model_Listener::class, $job->class );
		$this->assertSame( 'listeners', $job->queue );

		$this->dispatch_queue( queue: 'listeners' );

		$this->assertSame( [ [ $post->id(), 'Queued Listener', 'extra' ] ], $_SERVER['__queued_model_listener'] );
		$this->assertSame( 0, $this->app['queue']->get_provider()->pending_size( 'listeners' ) );
		$this->assertDatabaseHas( 'mantle_queue', [ 'queue' => 'listeners', 'status' => Status::COMPLETED->value ], 1 );
	}
}

class Queued_Model_Listener implements Can_Queue {
	public string $queue = 'listeners';

	public function handle( Post $post, string $extra ): void {
		$_SERVER['__queued_model_listener'][] = [ $post->id(), $post->title, $extra ];
	}
}
