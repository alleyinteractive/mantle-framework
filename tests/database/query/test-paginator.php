<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Database\Query\Post_Query_Builder as Builder;
use Mantle\Database\Query\Post_Query_Builder;
use Mantle\Facade\Route;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Framework_Test_Case;


class Test_Paginator extends Framework_Test_Case {
	use Refresh_Database;

	public function test_simple_paginate_url_query_string() {
		$paginator = Post::simple_paginate( 20 )->path( '/test-path/' );

		$this->assertEquals( '/test-path/', $paginator->url( 1 ) );
		$this->assertEquals( '/test-path/?page=10', $paginator->url( 10 ) );
		$this->assertEquals( '/test-path/', $paginator->url( -1 ) );
	}

	public function test_simple_paginate_url_path() {
		$paginator = Post::simple_paginate( 20 )
			->path( '/test-path/' )
			->use_path();

		$this->assertEquals( '/test-path/page/1/', $paginator->url( 1 ) );
		$this->assertEquals( '/test-path/page/10/', $paginator->url( 10 ) );
		$this->assertEquals( '/test-path/', $paginator->url( -1 ) );
	}

	public function test_simple_paginate_url_append() {
		$paginator = Post::simple_paginate()->append( 'key', 'value' );
		$this->assertEquals( '/?page=2&key=value', $paginator->url( 2 ) );
	}

	public function test_simple_paginate_url_append_path() {
		$paginator = Post::simple_paginate()->append( 'key', 'value' )->use_path();
		$this->assertEquals( '/page/2/?key=value', $paginator->url( 2 ) );
	}

	public function test_paginate_query_string() {
		$post_ids = array_reverse( static::factory()->post->create_many( 100 ) );

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( isset( $paginator) ) {
				$this->get( $paginator->next_url() );
			} else {
				$this->get( '/' );
			}

			$paginator = Post::simple_paginate( 20 );
			$expected = array_slice( $post_ids, ( $i -1 ) * 20, 20 );

			$this->assertEquals( $i, $paginator->current_page() );
			$this->assertEquals( $expected, $paginator->items()->pluck( 'ID' )->all(), 'Expected results for ' . $i );

			if ( $i > 2 ) {
				$this->assertEquals( '/?page=' . ( $i - 1 ), $paginator->previous_url() );
			} elseif ( $i > 1 ) {
				$this->assertEquals( '/', $paginator->previous_url() );
			} else {
				$this->assertNull( $paginator->previous_url() );
			}

			$this->assertEquals( '/?page=' . ( $i + 1 ), $paginator->next_url() );
		}
	}

	public function test_paginate_path() {
		$post_ids = array_reverse( static::factory()->post->create_many( 100 ) );

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( isset( $paginator) ) {
				$this->get( $paginator->next_url() );
			} else {
				$this->get( '/' );
			}

			$paginator = Post::simple_paginate( 20 )->use_path();
			$expected = array_slice( $post_ids, ( $i -1 ) * 20, 20 );

			$this->assertEquals( $i, $paginator->current_page() );
			$this->assertEquals( $expected, $paginator->items()->pluck( 'ID' )->all(), 'Expected results for ' . $i );

			if ( $i > 1 ) {
				$this->assertEquals( '/page/' . ( $i - 1 ) . '/', $paginator->previous_url() );
			} else {
				$this->assertNull( $paginator->previous_url() );
			}

			$this->assertEquals( '/page/' . ( $i + 1 ) . '/', $paginator->next_url() );
		}
	}

	public function test_paginate_render() {
		for ( $i = 1; $i <= 3; $i++ ) {
			if ( isset( $paginator) ) {
				$this->get( $paginator->next_url() );
			} else {
				$this->get( '/' );
			}

			$paginator = Post::simple_paginate( 20 );
			$render    = (string) $paginator->render();

			if ( $i > 2 ) {
				$this->assertStringContainsString( '<li><a href="/?page=' . ( $i - 1 ) . '" rel="prev">Previous</a></li>', $render );
			}

			$this->assertStringContainsString( '<li><a href="/?page=' . ( $i + 1 ) .'" rel="next">Next</a></li>', $render );
		}
	}

	public function test_paginate_render_length() {
		static::factory()->post->create_many( 100 );

		$max_pages = 5;

		for ( $i = 1; $i <= $max_pages; $i++ ) {
			if ( isset( $paginator) ) {
				$this->assertNotNull( $paginator->next_url() );
				$this->get( $paginator->next_url() );
			} else {
				$this->get( '/' );
			}

			$paginator = Post::paginate( 30 );
			$render    = (string) $paginator->render();

			$this->assertEquals( $i, $paginator->current_page() );

			// On the last page there should be no valid next link.
			if ( $i >= $max_pages ) {
				$this->assertFalse( $paginator->has_more() );
				$this->assertStringContainsString( '<li class="disabled" aria-disabled="true"><span>&rsaquo;</span></li>', $render );
			} else {
				$this->assertTrue( $paginator->has_more() );

				if ( $paginator->has_more() ) {
					$this->assertStringContainsString( '<li><a href="' . $paginator->next_url() . '" rel="next">&rsaquo;</a></li>', $render );
				}
			}

			// On page one the previous link should be disabled.
			if ( 1 === $i ) {
				$this->assertStringContainsString( '<li class="disabled" aria-disabled="true"><span>&lsaquo;</span></li>', $render );
			} else {
				$this->assertStringContainsString( '<li><a href="' . $paginator->previous_url() . '" rel="prev">&lsaquo;</a></li>', $render );

				if ( $paginator->has_more() ) {
					$this->assertStringContainsString( '<li><a href="/?page=' . ( $i + 1 ) . '"', $render );
				}
			}
		}
	}

	public function test_paginate_response() {
		$post_ids = static::factory()->post->create_many( 100 );

		Route::get( '/test-paginate', function() {
			return Post::paginate( 20 );
		} );

		$this->get( '/test-paginate' )
			->assertOk()
			->assertJsonPath( 'current_page', 1 )
			->assertJsonPath( 'previous_url', null )
			->assertJsonPath( 'next_url', '/test-paginate?page=2' )
			->assertJsonPath( 'path', '/test-paginate' )
			->assertHeader( 'content-type', 'application/json' )
			->assertJsonPath( 'data.0.ID', array_pop( $post_ids ) );

		$this->get( '/test-paginate?page=3' )
			->assertOk()
			->assertJsonPath( 'current_page', 3 )
			->assertJsonPath( 'previous_url', '/test-paginate?page=2' )
			->assertJsonPath( 'next_url', '/test-paginate?page=4' )
			->assertJsonPath( 'path', '/test-paginate' );
	}
}
