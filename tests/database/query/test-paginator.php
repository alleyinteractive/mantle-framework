<?php
namespace Mantle\Tests\Database\Builder;

use Mantle\Framework\Database\Model\Post;
use Mantle\Framework\Database\Model\Term;
use Mantle\Framework\Database\Query\Post_Query_Builder as Builder;
use Mantle\Framework\Database\Query\Post_Query_Builder;
use Mantle\Framework\Testing\Concerns\Refresh_Database;
use Mantle\Framework\Testing\Framework_Test_Case;


class Test_Paginator extends Framework_Test_Case {
	use Refresh_Database;

	public function test_simple_paginate_url_query_string() {
		$paginator = Post::simple_paginate( 20 )->path( '/test-path/' );

		$this->assertEquals( '/test-path/?page=1', $paginator->url( 1 ) );
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

			if ( $i > 1 ) {
				$this->assertEquals( '/?page=' . ( $i - 1 ), $paginator->previous_url() );
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
				$this->assertContains( '<li><a href="/?page=' . ( $i - 1 ) . '" rel="prev">Previous</a></li>', $render );
			}

			$this->assertContains( '<li><a href="/?page=' . ( $i + 1 ) .'" rel="next">Next</a></li>', $render );
		}
	}
}
