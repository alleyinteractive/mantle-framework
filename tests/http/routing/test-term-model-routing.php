<?php
namespace Mantle\Tests\Http\Routing;

use Mantle\Contracts\Database\Registrable;
use Mantle\Database\Model\Concerns\Custom_Term_Link;
use Mantle\Database\Model\Post;
use Mantle\Database\Model\Registration\Custom_Post_Permalink;
use Mantle\Database\Model\Registration\Register_Post_Type;
use Mantle\Database\Model\Registration\Register_Taxonomy;
use Mantle\Database\Model\Term;
use Mantle\Facade\Route;
use Mantle\Http\Controller;
use Mantle\Http\Routing\Middleware\Substitute_Bindings;
use Mantle\Http\Routing\Middleware\Wrap_Template;
use Mantle\Testing\Framework_Test_Case;

class Test_Term_Model_Routing extends Framework_Test_Case {
	public function test_category_term() {
		Testable_Category_Model::boot_if_not_booted();

		Route::middleware( [ Substitute_Bindings::class ] )->group( function () {
			Route::model( Testable_Category_Model::class, Testable_Term_Model_Controller::class );
		} );

		$category = Testable_Category_Model::create( [ 'name' => 'Example Category' ] );
		$post_id  = static::factory()->post->create();

		wp_set_post_categories( $post_id, [ $category->id() ], true );

		$term_link = get_term_link( $category->id(), 'category' );

		$this->assertEquals( home_url( '/custom-category-rewrite/' . $category->slug() ), $term_link );

		$this
			->get( $term_link )
			->assertSee( $category->slug() )
			->assertQueriedObject( get_term( $category->id() ) )
			->assertQueriedObjectId( $category->id() )
			->assertQueryTrue( 'is_archive', 'is_category', 'is_tax' );
	}

	public function test_custom_taxonomy() {
		Testable_Custom_Taxonomy_Model::register_object();

		Route::middleware( [ Substitute_Bindings::class ] )->group( function () {
			Route::model( Testable_Custom_Taxonomy_Model::class, Testable_Custom_Term_Model_Controller::class );
		} );

		$this->assertTrue( taxonomy_exists( Testable_Custom_Taxonomy_Model::get_object_name() ) );

		$term = Testable_Custom_Taxonomy_Model::create(
			[
				'name' => 'Example Title',
			]
		);

		$post_id = static::factory()->post->create();
		wp_set_object_terms( $post_id, [ $term->id() ], 'test_tax_test', true );

		$term_link = $term->permalink();

		$this->assertEquals( home_url( '/test_tax_test/' . $term->slug() . '/' ), $term_link );

		$this
			->get( $term_link )
			->assertContent( $term->slug() )
			->assertQueriedObject( get_term( $term->id() ) )
			->assertQueriedObjectId( $term->id() )
			->assertQueryTrue( 'is_archive', 'is_tax' );
	}
}

class Testable_Category_Model extends Term {
	use Custom_Term_Link;

	public static $object_name = 'category';

	public static function get_route(): ?string {
		return '/custom-category-rewrite/{category}';
	}
}

class Testable_Term_Model_Controller extends Controller {
	public function show( Testable_Category_Model $category  ) {
		return $category->slug();
	}
}

class Testable_Custom_Taxonomy_Model extends Term implements Registrable {
	use Register_Taxonomy;

	public static $object_name = 'test_tax_test';

	public static function get_registration_args(): array {
		return [
			'public'      => true,
			'object_type' => [ 'post' ],
		];
	}
}

class Testable_Custom_Term_Model_Controller extends Controller {
	public function show( Testable_Custom_Taxonomy_Model $test_tax_test  ) {
		return $test_tax_test->slug();
	}
}
