<?php
namespace Mantle\Tests\Framework\Http\Routing;

use Closure;
use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use Mantle\Framework\Facade\Route;
use Mantle\Framework\Http\Controller;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Post_Model_Routing extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		// delete_option( 'permalink_structure' );
		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
		flush_rewrite_rules();
	}

	// public function test_post_routing() {
	// 	Route::entity( Testable_Post_Model::class, Testable_Post_Model_Controller::class );

	// 	$post = Testable_Post_Model::find( static::factory()->post->create() );

	// 	$this->get( $post )->assertContent( $post->id() );
	// }

	public function test_custom_post_type_routing() {
		Testable_Custom_Post_Model::register_object();

		Route::model( Testable_Custom_Post_Model::class, Testable_Custom_Post_Model_Controller::class );

		$this->assertTrue( post_type_exists( Testable_Custom_Post_Model::get_object_name() ) );

		$post = Testable_Custom_Post_Model::create(
			[
				'title'  => 'Example Title',
				'status' => 'publish',
			]
		);

		$permalink = get_permalink( $post->ID );

		// Model permalinks and singular request.
		$this->assertEquals( home_url( '/test_cpt_routing/' . $post->slug ), $permalink );
		$this->get( $permalink )->assertContent( $post->slug() );

		// Post type archive.
		$archive_link = get_post_type_archive_link( Testable_Custom_Post_Model::get_object_name() );
		$this->assertEquals( home_url( '/test_cpt_routing' ), $archive_link );

		$this
			->get( $archive_link )
			->assertExactJson(
				[
					$post->ID,
				]
			);
	}
}

class Testable_Post_Model extends Post {
	public static $object_name = 'post';
}

class Testable_Custom_Post_Model extends Post implements Registrable {
	use Register_Post_Type;

	public static $object_name = 'test_cpt_routing';

	public static function get_registration_args(): array {
		return [
			'public'      => true,
			'has_archive' => true,
		];
	}
}

class Testable_Post_Model_Controller extends Controller {
	public function index() {
		return Testable_Post_Model::all()->each->pluck( 'id' );
	}

	public function show(Testable_Post_Model $post) {
		return $post->id();
	}
}

class Testable_Custom_Post_Model_Controller extends Controller {
	public function index() {
		return [ Testable_Custom_Post_Model::first()->id() ];
	}

	public function show( $slug ) {
		return $slug;
	}
}
