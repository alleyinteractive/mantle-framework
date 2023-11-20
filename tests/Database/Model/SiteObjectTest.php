<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Database\Model\Site;
use Mantle\Testing\Concerns\Multisite_Test;
use Mantle\Testing\Framework_Test_Case;


class SiteObjectTest extends Framework_Test_Case {
	use Multisite_Test;

	public function test_site_attributes() {
		$site_id = static::factory()->blog->create();
		$site    = Site::find( $site_id );

		$this->assertInstanceOf( Site::class, $site );
		$this->assertEquals( $site_id, $site->id() );

		$blogname = \get_blog_option( $site_id, 'blogname' );

		$object = get_site( $site->id() );

		$this->assertEquals( $blogname, $site->name() );
		$this->assertEquals( $object->path, $site->slug() );
		$this->assertEquals( get_home_url( $site_id ), $site->permalink() );
		$this->assertEquals( $object->public, $site->public );

		// Test that you can get the WordPress object.
		$core_object = $site->core_object();
		$this->assertInstanceOf( \WP_Site::class, $core_object );
		$this->assertEquals( $site->id(), $core_object->blog_id );
	}
}
