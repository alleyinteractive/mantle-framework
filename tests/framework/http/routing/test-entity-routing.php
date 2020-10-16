<?php
namespace Mantle\Tests\Framework\Http\Routing;

use Closure;
use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use Mantle\Framework\Facade\Route;
use Mantle\Framework\Http\Controller;
use Mantle\Framework\Http\Routing\Middleware\Substitute_Bindings;
use Mantle\Framework\Testing\Framework_Test_Case;
use WP_REST_Request;

class Test_Entity_Routing extends Framework_Test_Case {
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

		$this->assertTrue( post_type_exists( Testable_Custom_Post_Model::get_object_name() ) );

		Route::entity( Testable_Custom_Post_Model::class, Testable_Custom_Post_Model_Controller::class );

		$post = Testable_Custom_Post_Model::create(
			[
				'title'  => 'Example Title',
				'status' => 'publish',
			]
		);

		// Verify the URL matches the expected permalink.
		$this->assertEquals( home_url( '/custom-post-type/' . $post->slug() ), get_permalink( $post->id() ) );

		$this->get( $post )->assertContent( $post->slug() );

		// is_archive() test.
		$url = trailingslashit( get_post_type_archive_link(
			Testable_Custom_Post_Model::get_object_name()
		) );

		dd($url);
		dd($this->get( $url ));
	}
}

class Testable_Post_Model extends Post {
	public static $object_name = 'post';
}

class Testable_Custom_Post_Model extends Post implements Registrable {
	use Register_Post_Type;

	public static $object_name = 'test_cpt_model';

	public static function get_registration_args(): array {
		return [
			'public'       => true,
			'rest_base'    => static::get_object_name(),
			'show_in_rest' => true,
			'supports'     => [ 'author', 'title', 'editor', 'revisions', 'thumbnail', 'custom-fields', 'excerpt' ],
			'taxonomies'   => [ 'category', 'post_tag' ],
			'label'        => 'test-post-type',
			'has_archive'  => true,
		];
	}

	public static function get_route(): ?string {
		return '/custom-post-type/{post}';
	}

	public static function get_archive_route(): ?string {
			return '/custom-post-type/';
	}

	public function get_route_key() {
		return 'post_name';
	}
}

class Testable_Post_Model_Controller extends Controller {
	public function index() {
		dd(Testable_Post_Model::all()->each->pluck( 'id' ));
		return Testable_Post_Model::all()->each->pluck( 'id' );
	}

	public function show(Testable_Post_Model $post) {
		return $post->id();
	}
}

class Testable_Custom_Post_Model_Controller extends Controller {
	public function index() {
		dd(Testable_Custom_Post_Model::all()->each->pluck( 'id' ));
		return Testable_Custom_Post_Model::all()->each->pluck( 'id' );
	}

	public function show($slug) {
		return $slug;
	}
}
