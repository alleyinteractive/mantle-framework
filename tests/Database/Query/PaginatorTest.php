<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Database\Model\Post;
use Mantle\Facade\Route;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\FrameworkTestCase;

class PaginatorTest extends FrameworkTestCase {
	use Refresh_Database;

	public function test_simple_paginate_url_query_string(): void {
		$paginator = Post::simple_paginate( 20 )->path( '/test-path/' );

		$this->assertSame( '/test-path/', $paginator->url( 1 ) );
		$this->assertSame( '/test-path/?page=10', $paginator->url( 10 ) );
		$this->assertSame( '/test-path/', $paginator->url( -1 ) );
	}

	public function test_simple_paginate_url_path(): void {
		$paginator = Post::simple_paginate( 20 )
			->path( '/test-path/' )
			->use_path();

		$this->assertSame( '/test-path/page/1/', $paginator->url( 1 ) );
		$this->assertSame( '/test-path/page/10/', $paginator->url( 10 ) );
		$this->assertSame( '/test-path/', $paginator->url( -1 ) );
	}

	public function test_simple_paginate_url_append(): void {
		$paginator = Post::simple_paginate()->append( 'key', 'value' );
		$this->assertSame( '/?page=2&key=value', $paginator->url( 2 ) );
	}

	public function test_simple_paginate_url_append_path(): void {
		$paginator = Post::simple_paginate()->append( 'key', 'value' )->use_path();
		$this->assertSame( '/page/2/?key=value', $paginator->url( 2 ) );
	}

	public function test_paginate_query_string(): void {
		$post_ids = array_reverse( static::factory()->post->create_many( 100 ) );

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( isset( $paginator) ) {
				$this->get( $paginator->next_url() )
					->assertOk();
			} else {
				$this->get( '/' )
					->assertOk();
			}

			$paginator = Post::simple_paginate( 20 );
			$expected = array_slice( $post_ids, ( $i -1 ) * 20, 20 );

			$this->assertSame( $i, $paginator->current_page() );
			$this->assertSame( $expected, $paginator->items()->pluck( 'ID' )->all(), 'Expected results for ' . $i );

			if ( $i > 2 ) {
				$this->assertSame( '/?page=' . ( $i - 1 ), $paginator->previous_url() );
			} elseif ( $i > 1 ) {
				$this->assertSame( '/', $paginator->previous_url() );
			} else {
				$this->assertNull( $paginator->previous_url() );
			}

			$this->assertSame( '/?page=' . ( $i + 1 ), $paginator->next_url() );
		}
	}

	public function test_paginate_path(): void {
		$post_ids = array_reverse( static::factory()->post->create_many( 100 ) );

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( isset( $paginator) ) {
				$this->get( $paginator->next_url() )
					->assertOk();
			} else {
				$this->get( '/' )
					->assertOk();
			}

			$paginator = Post::simple_paginate( 20 )->use_path();
			$expected = array_slice( $post_ids, ( $i -1 ) * 20, 20 );

			$this->assertSame( $i, $paginator->current_page() );
			$this->assertSame( $expected, $paginator->items()->pluck( 'ID' )->all(), 'Expected results for ' . $i );

			if ( $i > 1 ) {
				$this->assertSame( '/page/' . ( $i - 1 ) . '/', $paginator->previous_url() );
			} else {
				$this->assertNull( $paginator->previous_url() );
			}

			$this->assertSame( '/page/' . ( $i + 1 ) . '/', $paginator->next_url() );
		}
	}

	public function test_simple_paginate_has_more(): void {
		$this->assertFalse( Post::simple_paginate( 2 )->has_more() );
		$this->assertNull( Post::simple_paginate( 2 )->next_url() );

		static::factory()->post->create_many( 3 );

		$this->assertTrue( Post::simple_paginate( 2 )->has_more() );
		$this->assertCount( 2, Post::simple_paginate( 2 )->items() );
		$this->assertFalse( Post::simple_paginate( 2, 2 )->has_more() );
		$this->assertCount( 1, Post::simple_paginate( 2, 2 )->items() );
		$this->assertFalse( Post::simple_paginate( 3 )->has_more() );
	}

	public function test_paginate_render(): void {
		static::factory()->post->create_many( 5 );

		for ( $i = 1; $i <= 3; $i++ ) {
			if ( isset( $paginator) ) {
				$this->get( $paginator->next_url() )
					->assertOk();
			} else {
				$this->get( '/' )
					->assertOk();
			}

			$paginator = Post::simple_paginate( 2 );
			$render    = (string) $paginator->render();

			if ( $i > 2 ) {
				$this->assertStringContainsString( '<li><a href="/?page=' . ( $i - 1 ) . '" rel="prev">Previous</a></li>', $render );
			}

			if ( $i < 3 ) {
				$this->assertStringContainsString( '<li><a href="/?page=' . ( $i + 1 ) .'" rel="next">Next</a></li>', $render );
			} else {
				$this->assertStringNotContainsString( 'rel="next"', $render );
				$this->assertNull( $paginator->next_url() );
			}
		}
	}

	public function test_paginate_render_length(): void {
		static::factory()->post->create_many( 7 );

		$max_pages = 4;

		for ( $i = 1; $i <= $max_pages; $i++ ) {
			if ( isset( $paginator) ) {
				$this->assertNotNull( $paginator->next_url() );
				$this->get( $paginator->next_url() )
					->assertOk();
			} else {
				$this->get( '/' )
					->assertOk();
			}

			$paginator = Post::paginate( 2 );
			$render    = (string) $paginator->render();

			$this->assertSame( $i, $paginator->current_page() );
			$this->assertSame( $max_pages, $paginator->max_pages() );
			$this->assertSame( $i > 1, $paginator->has_previous() );
			$this->assertContains( $max_pages, array_map( 'key', $paginator->elements() ) );

			// On the last page there should be no valid next link.
			if ( $i >= $max_pages ) {
				$this->assertFalse( $paginator->has_more() );
				$this->assertNull( $paginator->next_url() );
				$this->assertStringContainsString( '<li class="disabled" aria-disabled="true"><span>&rsaquo;</span></li>', $render );
			} else {
				$this->assertTrue( $paginator->has_more() );
				$this->assertStringContainsString( '<li><a href="' . $paginator->next_url() . '" rel="next">&rsaquo;</a></li>', $render );
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

	public function test_paginate_response(): void {
		$post_ids = static::factory()->post->create_many( 100 );

		Route::get( '/test-paginate', static function() {
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
