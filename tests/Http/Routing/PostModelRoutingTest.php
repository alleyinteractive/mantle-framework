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
use Mantle\Testing\FrameworkTestCase;

class PostModelRoutingTest extends FrameworkTestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->set_permalink_structure( '/%year%/%monthnum%/%day%/%postname%/' );

		add_filter( 'deprecated_file_trigger_error', '__return_false' );
	}

	public function test_post_type(): void {
		Testable_Post_Model::boot_if_not_booted();

		Route::middleware( [ Substitute_Bindings::class, Wrap_Template::class ] )->group( static function () {
			Route::model( Testable_Post_Model::class, Testable_Post_Model_Controller::class );
		} );

		$post = Testable_Post_Model::create(
			[
				'title'  => 'Example Post',
				'status' => 'publish',
			]
		);

		$core_post = get_post( $post->id() );

		$this->assertInstanceOf( \WP_Post::class, $core_post );

		$this
			->get( $post )
			->assertOk()
			->assertSee( $post->slug() )
			->assertQueriedObject( $core_post )
			->assertQueryTrue( 'is_singular', 'is_single' );
	}

	public function test_custom_post_type(): void {
		Testable_Custom_Post_Model::register_object();

		Route::model( Testable_Custom_Post_Model::class, Testable_Custom_Post_Model_Controller::class );

		$post_type = Testable_Custom_Post_Model::get_object_name();

		$this->assertIsString( $post_type );
		$this->assertTrue( post_type_exists( $post_type ) );

		$post = Testable_Custom_Post_Model::create(
			[
				'title'  => 'Example Title',
				'status' => 'publish',
			]
		);

		$permalink = get_permalink( $post->ID );

		$this->assertSame( home_url( '/test_cpt_routing/' . $post->slug ), $permalink );
		$this->get( $permalink )
			->assertOk()
			->assertContent( $post->slug() );

		$archive_link = get_post_type_archive_link( $post_type );
		$this->assertSame( home_url( '/test_cpt_routing' ), $archive_link );

		$this
			->get( $archive_link )
			->assertOk()
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
	public function show( string $year, string $month, string $date, Testable_Post_Model $post ): string {
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
	/**
	 * @return array<int, int|null>
	 */
	public function index(): array {
		return [ Testable_Custom_Post_Model::first()->id() ];
	}

	public function show( string $slug ): string {
		return $slug;
	}
}
