<?php
namespace Mantle\Tests\Http\Routing;

use Mantle\Contracts\Database\Registrable;
use Mantle\Database\Model\Concerns\Custom_Post_Permalink;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Register_Post_Type;
use Mantle\Facade\Route;
use Mantle\Http\Controller;
use Mantle\Http\Routing\Middleware\Substitute_Bindings;
use Mantle\Http\Routing\Middleware\Wrap_Template;
use Mantle\Testing\Framework_Test_Case;

class Test_Post_Model_Routing extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		add_filter( 'deprecated_file_trigger_error', '__return_false' );
	}

	public function test_post_type() {
		Testable_Post_Model::boot_if_not_booted();

		Route::middleware( [ Substitute_Bindings::class, Wrap_Template::class ] )->group( function () {
			Route::model( Testable_Post_Model::class, Testable_Post_Model_Controller::class );
		} );

		$post = Testable_Post_Model::create(
			[
				'title'  => 'Example Post',
				'status' => 'publish',
			]
		);

		$this
			->get( $post )
			->assertSee( $post->slug() )
			->assertQueriedObject( get_post( $post->id() ) )
			->assertQueryTrue( 'is_singular', 'is_single' );
	}

	public function test_custom_post_type() {
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

		$this->assertEquals( home_url( '/test_cpt_routing/' . $post->slug ), $permalink );
		$this->get( $permalink )->assertContent( $post->slug() );

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

class Testable_Post_Model_Controller extends Controller {
	public function show( string $year, string $month, string $date, Testable_Post_Model $post ) {
		return $post->slug();
	}
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

class Testable_Custom_Post_Model_Controller extends Controller {
	public function index() {
		return [ Testable_Custom_Post_Model::first()->id() ];
	}

	public function show( $slug ) {
		return $slug;
	}
}
