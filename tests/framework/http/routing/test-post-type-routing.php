<?php
namespace Mantle\Tests\Framework\Http\Routing;

use Closure;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Facade\Route;
use Mantle\Framework\Http\Controller;
use Mantle\Framework\Testing\Framework_Test_Case;
use WP_REST_Request;

class Test_Post_Type_Routing extends Framework_Test_Case {
	protected function setUp(): void {
		parent::setUp();

		update_option( 'permalink_structure', '/%year%/%monthnum%/%day%/%postname%/' );
	}

	public function test_post_routing() {
		Route::post_type( Testable_Post_Model::class, Testable_Post_Model_Controller::class );
	}
}

class Testable_Post_Model extends Post {}

class Testable_Post_Model_Controller extends Controller {
	public function index() {
		dd(Testable_Post_Model::all()->each->pluck( 'id' ));
		return Testable_Post_Model::all()->each->pluck( 'id' );
	}

	public function show(Testable_Post_Model $post) {
		return $post->id();
	}
}
