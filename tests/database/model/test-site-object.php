<?php
namespace Mantle\Tests\Database\Model;

use Mantle\Framework\Database\Model\Site;
use WP_UnitTestCase;

/**
 * @todo Replace with the Mantle Testing Framework
 */
class Test_Site_Object extends WP_UnitTestCase {
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
	}
}
