<?php
namespace Mantle\Tests\Testing;

use Mantle\Testing\FrameworkTestCase;
use Mantle\Testing\Utils;

class UtilsTest extends FrameworkTestCase {
	public function test_delete_all_posts(): void {
		$post     = static::factory()->post->create_and_get();
		$category = static::factory()->category->create_and_get();
		$user     = static::factory()->user->create_and_get();

		$this->assertPostExists( [ 'post_title' => $post->post_title ] );
		$this->assertTermExists( [ 'name' => $category->name ] );
		$this->assertUserExists( [ 'user_login' => $user->user_login ] );

		Utils::delete_all_posts();

		$this->assertPostDoesNotExists( [ 'post_title' => $post->post_title ] );
		$this->assertTermExists( [ 'name' => $category->name ] );
		$this->assertUserExists( [ 'user_login' => $user->user_login ] );
	}

	public function test_delete_all_data(): void {
		$post     = static::factory()->post->create_and_get();
		$category = static::factory()->category->create_and_get();
		$user     = static::factory()->user->create_and_get();

		$this->assertPostExists( [ 'post_title' => $post->post_title ] );
		$this->assertTermExists( [ 'name' => $category->name ] );
		$this->assertUserExists( [ 'login' => $user->user_login ] );

		Utils::delete_all_data();

		$this->assertPostDoesNotExists( [ 'post_title' => $post->post_title ] );
		$this->assertTermDoesNotExists( [ 'name' => $category->name ] );
		$this->assertUserDoesNotExists( [ 'login' => $user->user_login ] );
	}

	public function test_delete_all_blogs(): void {
		$this->skipWithoutMultisite();

		$blog = static::factory()->blog->create_and_get();

		$this->assertBlogExists( [ 'ID' => $blog->blog_id ] );
		$this->assertDatabaseHas( 'blogs', [ 'blog_id' => $blog->blog_id ] );

		Utils::delete_all_blogs();

		$this->assertDatabaseMissing( 'blogs', [ 'blog_id' => $blog->blog_id ] );
		$this->assertBlogDoesNotExist( [ 'ID' => $blog->blog_id ] );
	}

	public function test_content_directory_name_defaults_to_wp_content(): void {
		$this->assertSame( 'wp-content', Utils::content_directory_name() );
	}

	public function test_content_directory_name_honors_environment_variable(): void {
		putenv( 'WP_CONTENT_DIR_NAME=app' );

		try {
			$this->assertSame( 'app', Utils::content_directory_name() );
		} finally {
			putenv( 'WP_CONTENT_DIR_NAME' );
		}
	}

	public function test_content_directory_name_is_normalized(): void {
		putenv( 'WP_CONTENT_DIR_NAME=/app/' );

		try {
			$this->assertSame( 'app', Utils::content_directory_name() );
		} finally {
			putenv( 'WP_CONTENT_DIR_NAME' );
		}
	}
}
