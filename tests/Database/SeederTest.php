<?php

namespace Mantle\Tests\Database;

use Mantle\Database\Seeder;
use Mantle\Testing\Framework_Test_Case;

class SeederTest extends Framework_Test_Case {
	public function test_it_can_seed_data(): void {
		$_SERVER['__seeder_run'] = false;

		$seeder = $this->app->make( TestableSeeder::class );
		$seeder->set_container( $this->app );

		$seeder();

		$this->assertTrue( $_SERVER['__seeder_run'] );
	}

	public function test_it_can_seed_data_with_factory(): void {
		$this->assertPostDoesNotExists( [
			'post_title' => 'Seeded post',
		] );

		$seeder = $this->app->make( TestableSeederWithFactory::class );
		$seeder->set_container( $this->app );

		$seeder();

		$this->assertPostExists( [
			'post_title' => 'Seeded post',
		] );
	}

	public function test_it_can_call_another_seeder(): void {
		$_SERVER['__seeder_run'] = false;

		$seeder = $this->app->make( TestableSeederCallsAnotherSeeder::class );
		$seeder->set_container( $this->app );

		$seeder();

		$this->assertTrue( $_SERVER['__seeder_run'] );
	}
}

class TestableSeeder extends Seeder {
	public function run(): void {
		$_SERVER['__seeder_run'] = true;
	}
}

class TestableSeederWithFactory extends Seeder {
	public function run(): void {
		$this->factory()->post->create( [ 'post_title' => 'Seeded post' ] );
	}
}

class TestableSeederCallsAnotherSeeder extends Seeder {
	public function run(): void {
		$this->call( TestableSeeder::class );
	}
}
