<?php
namespace Mantle\Tests\Framework\Http\Routing;

use Mantle\Framework\Contracts\Database\Registrable;
use Mantle\Framework\Database\Model\Concerns\Custom_Term_Link;
use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Registration\Custom_Post_Permalink;
use Mantle\Framework\Database\Model\Registration\Register_Post_Type;
use Mantle\Framework\Database\Model\Registration\Register_Taxonomy;
use Mantle\Framework\Database\Model\Term;
use Mantle\Facade\Route;
use Mantle\Framework\Http\Controller;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_Taxonomy_Model_Routing extends Framework_Test_Case {
	public function test_category_term() {
		Testable_Category_Model::boot_if_not_booted();

		Route::model( Testable_Category_Model::class, Testable_Term_Model_Controller::class );

		$category = Testable_Category_Model::create( [ 'name' => 'Example Category' ] );
		$post_id  = static::factory()->post->create();

		wp_set_post_categories( $post_id, [ $category->id() ], true );

		$term_link = get_term_link( $category->id(), 'category' );

		$this->assertEquals( home_url( '/custom-category-rewrite/' . $category->slug() ), $term_link );

		$this->get( $term_link )->assertContent( $category->slug() );
	}

	public function test_custom_taxonomy() {
		Testable_Custom_Taxonomy_Model::register_object();

		Route::model( Testable_Custom_Taxonomy_Model::class, Testable_Term_Model_Controller::class );

		$this->assertTrue( taxonomy_exists( Testable_Custom_Taxonomy_Model::get_object_name() ) );

		$term = Testable_Custom_Taxonomy_Model::create(
			[
				'name' => 'Example Title',
			]
		);

		$post_id = static::factory()->post->create();
		wp_set_object_terms( $post_id, [ $term->id() ], 'test_tax_test', true );

		$term_link = $term->permalink();

		$this->assertEquals( home_url( '/test_tax_test/' . $term->slug() ), $term_link );

		$this->get( $term_link )->assertContent( $term->slug() );
	}
}

class Testable_Category_Model extends Term {
	use Custom_Term_Link;

	public static $object_name = 'category';

	public static function get_route(): ?string {
		return '/custom-category-rewrite/{slug}';
	}
}

class Testable_Term_Model_Controller extends Controller {
	public function show( $slug ) {
		return $slug;
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
