<?php
namespace Mantle\Tests\Support\Registration;

use Mantle\Support\Registration\Post_Type_Arguments;
use PHPUnit\Framework\TestCase;

class PostTypeArgumentsTest extends TestCase {
	public function test_with_label(): void {
		$this->assertSame(
			[
				'labels' => [
					'name' => 'Test Labels',
					'singular_name' => 'Test Label',
					'add_new' => 'Add New Test Label',
					'add_new_item' => 'Add New Test Label',
					'edit_item' => 'Edit Test Label',
					'new_item' => 'New Test Label',
					'view_item' => 'View Test Label',
					'view_items' => 'View Test Labels',
					'search_items' => 'Search Test Labels',
					'not_found' => 'No test labels found.',
					'not_found_in_trash' => 'No test labels found in Trash.',
					'parent_item_colon' => 'Parent Item:',
					'all_items' => 'All Test Labels',
					'archives' => 'Test Label Archives',
					'attributes' => 'Test Label Attributes',
					'insert_into_item' => 'Insert into test label',
					'uploaded_to_this_item' => 'Uploaded to this test label',
					'featured_image' => 'Featured Image',
					'set_featured_image' => 'Set featured image',
					'remove_featured_image' => 'Remove featured image',
					'use_featured_image' => 'Use as featured image',
					'menu_name' => 'Test Labels',
					'filter_items_list' => 'Filter test labels list',
					'filter_by_date' => 'Filter by date',
					'items_list_navigation' => 'Test Labels list navigation',
					'items_list' => 'Test Labels list',
					'item_published' => 'Test Label published.',
					'item_published_privately' => 'Test Label published privately.',
					'item_reverted_to_draft' => 'Test Label reverted to draft.',
					'item_trashed' => 'Test Label trashed.',
					'item_scheduled' => 'Test Label scheduled.',
					'item_updated' => 'Test Label updated.',
					'item_link' => 'Test Label Link',
					'item_link_description' => 'A link to a test label.',
				],
			],
			Post_Type_Arguments::make()
				->label( 'Test Label' )
				->to_array()
		);
	}

	public function test_with_labels(): void {
		$this->assertSame(
			[
				'labels' => [
					'example' => 'key',
				],
			],
			Post_Type_Arguments::make()
				->labels( 'example', 'key' )
				->to_array()
		);
	}

	public function test_with_description(): void {
		$this->assertSame(
			[ 'description' => 'Test Description' ],
			Post_Type_Arguments::make()
				->description( 'Test Description' )
				->to_array()
		);
	}

	public function test_with_public(): void {
		$this->assertSame(
			[ 'public' => true ],
			Post_Type_Arguments::make()
				->public( true )
				->to_array()
		);
	}

	public function test_with_hierarchical(): void {
		$this->assertSame(
			[ 'hierarchical' => true ],
			Post_Type_Arguments::make()
				->hierarchical( true )
				->to_array()
		);
	}

	public function test_with_exclude_from_search(): void {
		$this->assertSame(
			[ 'exclude_from_search' => true ],
			Post_Type_Arguments::make()
				->exclude_from_search( true )
				->to_array()
		);
	}

	public function test_with_publicly_queryable(): void {
		$this->assertSame(
			[ 'publicly_queryable' => true ],
			Post_Type_Arguments::make()
				->publicly_queryable( true )
				->to_array()
		);
	}

	public function test_with_show_ui(): void {
		$this->assertSame(
			[ 'show_ui' => true ],
			Post_Type_Arguments::make()
				->show_ui( true )
				->to_array()
		);
	}

	public function test_with_show_in_menu(): void {
		$this->assertSame(
			[ 'show_in_menu' => true ],
			Post_Type_Arguments::make()
				->show_in_menu( true )
				->to_array()
		);
	}

	public function test_with_show_in_admin_bar(): void {
		$this->assertSame(
			[ 'show_in_admin_bar' => true ],
			Post_Type_Arguments::make()
				->show_in_admin_bar( true )
				->to_array()
		);
	}

	public function test_with_show_in_nav_menus(): void {
		$this->assertSame(
			[ 'show_in_nav_menus' => true ],
			Post_Type_Arguments::make()
				->show_in_nav_menus( true )
				->to_array()
		);
	}

	public function test_with_show_in_rest(): void {
		$this->assertSame(
			[ 'show_in_rest' => true ],
			Post_Type_Arguments::make()
				->show_in_rest( true )
				->to_array()
		);
	}

	public function test_with_rest_base(): void {
		$this->assertSame(
			[ 'rest_base' => 'test-rest-base' ],
			Post_Type_Arguments::make()
				->rest_base( 'test-rest-base' )
				->to_array()
		);
	}

	public function test_with_rest_namespace(): void {
		$this->assertSame(
			[ 'rest_namespace' => 'test-rest-namespace' ],
			Post_Type_Arguments::make()
				->rest_namespace( 'test-rest-namespace' )
				->to_array()
		);
	}

	public function test_with_rest_controller_class(): void {
		$this->assertSame(
			[ 'rest_controller_class' => 'test-rest-controller-class' ],
			Post_Type_Arguments::make()
				->rest_controller_class( 'test-rest-controller-class' )
				->to_array()
		);
	}

	public function test_with_capability_type(): void {
		$this->assertSame(
			[ 'capability_type' => 'test-capability-type' ],
			Post_Type_Arguments::make()
				->capability_type( 'test-capability-type' )
				->to_array()
		);
	}

	public function test_menu_position_and_icon(): void {
		$this->assertSame(
			[
				'menu_position' => 5,
				'menu_icon'     => 'dashicons-test',
			],
			Post_Type_Arguments::make()
				->menu_position( 5 )
				->menu_icon( 'dashicons-test' )
				->to_array()
		);
	}

	public function test_with_capabilities(): void {
		$this->assertSame(
			[
				'capabilities' => [
					'edit_post' => 'edit_test_post',
					'delete_post' => 'delete_test_post',
				],
			],
			Post_Type_Arguments::make()
				->capabilities( [
					'edit_post'   => 'edit_test_post',
					'delete_post' => 'delete_test_post',
				] )
				->to_array()
		);
	}

	public function test_with_map_meta_cap(): void {
		$this->assertSame(
			[ 'map_meta_cap' => true ],
			Post_Type_Arguments::make()
				->map_meta_cap( true )
				->to_array()
		);
	}

	public function test_with_supports(): void {
		$this->assertSame(
			[ 'supports' => [ 'title', 'editor' ] ],
			Post_Type_Arguments::make()
				->supports( [ 'title', 'editor' ] )
				->to_array()
		);

		$this->assertSame(
			[ 'supports' => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'revisions' ] ],
			Post_Type_Arguments::make()
				->default_supports()
				->to_array()
		);

		$this->assertSame(
			[ 'supports' => [ 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'comments' ] ],
			Post_Type_Arguments::make()
				->default_supports()
				->remove_support( 'revisions' )
				->to_array()
		);
	}

	public function test_with_taxonomies(): void {
		$this->assertSame(
			[ 'taxonomies' => [ 'category', 'post_tag' ] ],
			Post_Type_Arguments::make()
				->taxonomies( [ 'category', 'post_tag' ] )
				->to_array()
		);
	}

	public function test_with_has_archive(): void {
		$this->assertSame(
			[ 'has_archive' => true ],
			Post_Type_Arguments::make()
				->has_archive( true )
				->to_array()
		);
	}

	public function test_with_rewrite(): void {
		$this->assertSame(
			[ 'rewrite' => [ 'slug' => 'test-slug' ] ],
			Post_Type_Arguments::make()
				->rewrite( [ 'slug' => 'test-slug' ] )
				->to_array()
		);
	}

	public function test_with_query_var(): void {
		$this->assertSame(
			[ 'query_var' => 'test-query-var' ],
			Post_Type_Arguments::make()
				->query_var( 'test-query-var' )
				->to_array()
		);
	}

	public function test_with_template(): void {
		$this->assertSame(
			[ 'template' => [ [ 'core/paragraph', [ 'placeholder' => 'Test template' ] ] ] ],
			Post_Type_Arguments::make()
				->template( [ [ 'core/paragraph', [ 'placeholder' => 'Test template' ] ] ] )
				->to_array()
		);
	}

	public function test_arbitrary(): void {
		$this->assertSame(
			[ 'arbitrary' => 'test-arbitrary' ],
			Post_Type_Arguments::make()
				->arbitrary( 'test-arbitrary' )
				->to_array()
		);
	}
}
