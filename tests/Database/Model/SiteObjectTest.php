<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Site;
use Mantle\Testing\Concerns\Multisite_Test;
use Mantle\Testing\FrameworkTestCase;

class SiteObjectTest extends FrameworkTestCase {
	use Multisite_Test;

	public function test_site_attributes(): void {
		$site_id = static::factory()->blog->create();
		$site    = Site::find( $site_id );

		$this->assertInstanceOf( Site::class, $site );
		$this->assertSame( $site_id, $site->id() );

		$blogname = \get_blog_option( $site_id, 'blogname' );

		$object = get_site( $site->id() );

		$this->assertInstanceOf( \WP_Site::class, $object );

		$this->assertSame( $blogname, $site->name() );
		$this->assertSame( $object->path, $site->slug() );
		$this->assertSame( get_home_url( $site_id ), $site->permalink() );
		$this->assertSame( $object->public, $site->public );

		// Test that you can get the WordPress object.
		$core_object = $site->core_object();
		$this->assertInstanceOf( \WP_Site::class, $core_object );
		$this->assertSame( $site->id(), (int) $core_object->blog_id );
	}
}
