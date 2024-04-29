<?php

namespace Mantle\Tests\Framework;

use Mantle\Application\Application;
use Mantle\Framework\Bootloader;
use Mantle\Framework\Bootstrap\Register_Providers;
use Mantle\Http\Request;
use Mantle\Http\Response;
use Mantle\Support\Service_Provider;
use Mantle\Testing\Concerns\Interacts_With_Hooks;
use PHPUnit\Framework\TestCase;

class BootloaderTest extends TestCase {
	use Interacts_With_Hooks;

	public function setUp(): void {
		parent::setUp();

		Bootloader::clear_instance();
		Register_Providers::flush();

		$this->interacts_with_hooks_set_up();
	}

	public function tearDown(): void {
		$this->interacts_with_hooks_tear_down();

		Bootloader::clear_instance();
		Register_Providers::flush();

		parent::tearDown();
	}

	public function test_it_can_create_an_instance() {
		$this->assertInstanceOf( Bootloader::class, Bootloader::get_instance() );
		$this->assertNotNull( Bootloader::get_instance()->get_base_path() );
	}

	public function test_it_set_basepath_from_env() {
		$_ENV['MANTLE_BASE_PATH'] = '/foo/bar';

		$this->assertSame( '/foo/bar', Bootloader::get_instance()->get_base_path() );
	}

	public function test_it_can_be_used_by_helper() {
		$this->assertInstanceOf( Bootloader::class, bootloader() );
	}

	public function test_it_will_set_instance_on_construct() {
		$manager = new Bootloader();

		$this->assertSame( $manager, Bootloader::get_instance() );
	}

	public function test_it_can_bind_custom_kernel() {
		bootloader()
			->with_kernels( http_kernel: Testable_Http_Kernel::class )
			->boot();

		// Ensure the custom kernel was bound.
		$this->assertInstanceOf(
			Testable_Http_Kernel::class,
			app( \Mantle\Contracts\Http\Kernel::class ),
		);

		// Ensure the standard console kernel was still bound.
		$this->assertInstanceOf(
			\Mantle\Framework\Console\Kernel::class,
			app( \Mantle\Contracts\Console\Kernel::class ),
		);
	}

	public function test_it_can_boot_application() {
		$this->expectApplied( 'mantle_bootloader_before_boot' )->once();
		$this->expectApplied( 'mantle_bootloader_booted' )->once();

		// Path filters.
		$this->expectApplied( 'mantle_bootstrap_path' )->once()->andReturnString();
		$this->expectApplied( 'mantle_cache_path' )->andReturnString();
		$this->expectApplied( 'mantle_storage_path' )->once()->andReturnString();

		( new Bootloader() )->boot();

		$this->assertNotEmpty(
			app()->make( \Mantle\Contracts\Console\Kernel::class ),
		);

		$this->assertNotEmpty(
			app()->make( \Mantle\Contracts\Http\Kernel::class ),
		);

		$this->assertNotEmpty(
			app()->make( \Mantle\Contracts\Exceptions\Handler::class ),
		);

		$this->assertTrue( app()->has_been_bootstrapped() );
	}

	public function test_it_can_setup_routing() {
		add_filter( 'wp_using_themes', fn () => true, 99 );

		$manager = new Bootloader();

		$manager->boot();

		$app = $manager->get_application();

		$this->assertTrue( $app->bound( 'router' ) );

		// Register the route.
		$app->make( 'router' )->get( '/example-router', fn () => 'Hello World' );

		$request = Request::create( '/example-router' );

		$app->instance( 'request', $request );

		$kernel = $app->make( \Mantle\Contracts\Http\Kernel::class );

		// Make the request through the kernel.
		$response = $kernel->send_request_through_router( $request );

		$this->assertInstanceof( Response::class, $response );
		$this->assertSame( 'Hello World', $response->getContent() );
	}

	public function test_it_can_setup_providers() {
		$_SERVER['__test_service_provider_register__'] = false;
		$_SERVER['__test_service_provider_boot__']    = false;

		$manager = new Bootloader();
		$manager->with_providers( [ Test_Service_Provider::class ] );

		$manager->boot();

		$this->assertTrue( $_SERVER['__test_service_provider_register__'] );
		$this->assertTrue( $_SERVER['__test_service_provider_boot__'] );
	}

	public function test_it_can_setup_config() {
		( new Bootloader() )
			->with_config( [
				'app' => [
					'merged' => 'value',
					'debug' => false,
				],
			] )
			->boot();

		$this->assertEquals( 'value', config( 'app.merged' ) );
		$this->assertFalse( config( 'app.debug' ) );
	}
}

class Testable_Http_Kernel implements \Mantle\Contracts\Http\Kernel {
	/**
	 * Run the HTTP Application.
	 *
	 * @param Request $request Request object.
	 */
	public function handle( Request $request ) {
		$_SERVER['__testable_http_kernel__'] = $request;
	}

	/**
	 * Terminate the HTTP request.
	 *
	 * @param Request  $request  Request object.
	 * @param mixed    $response Response object.
	 * @return void
	 */
	public function terminate( Request $request, mixed $response ): void {
		$_SERVER['__testable_http_kernel_terminate__'] = $request;
	}
}

class Test_Service_Provider extends Service_Provider {
	public function register() {
		$_SERVER['__test_service_provider_register__'] = true;
	}

	public function boot() {
		$_SERVER['__test_service_provider_boot__'] = true;
	}
}
