<?php
namespace Mantle\Tests\Database\Model;

use Faker\Factory;
use Mantle\Framework\Database\Model\User;
use Mantle\Framework\Testing\Framework_Test_Case;

class Test_User_Object extends Framework_Test_Case {
	/**
	 * @var Factory
	 */
	protected static $faker;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		static::$faker = Factory::create();
	}

	public function test_find_user() {
		$user_id = $this->get_random_user_id();
		$object = User::find( $user_id );
		$user = get_user_by( 'ID', $user_id );

		$this->assertEquals( $user_id, $object->id() );
		$this->assertEquals( $user->user_login, $object->slug() );
		$this->assertEquals( $user->display_name, $object->name() );
		$this->assertEquals( $user->user_email, $object->get( 'email' ) );
		$this->assertEquals( $user->user_email, $object->email );
		$this->assertEquals( $user->user_email, $object->user_email );

		// Test that you can get the WordPress object.
		$core_object = $object->core_object();
		$this->assertInstanceOf( \WP_User::class, $core_object );
		$this->assertEquals( $object->id(), $user_id );
	}

	public function test_create_user() {
		$email = static::$faker->email;

		$user = new User(
			[
				'email' => $email,
				'slug'  => static::$faker->userName,
				'password' => static::$faker->password,
			]
		);

		$user->save();
		$this->assertNotEmpty( $user->id() );

		// Get the created user by email.
		$user_by_email = get_user_by( 'email', $email );
		$this->assertInstanceOf( \WP_User::class, $user_by_email );
		$this->assertEquals( $user->id(), $user_by_email->ID );
	}

	public function test_updating_user() {
		$user_id = $this->get_random_user_id();
		$user    = User::find( $user_id );

		$email = static::$faker->email;
		$user->email = $email;
		$user->save();

		// Get the created user by the updated email.
		$user_by_email = get_user_by( 'email', $email );
		$this->assertInstanceOf( \WP_User::class, $user_by_email );
		$this->assertEquals( $user_id, $user_by_email->ID );
	}

	public function test_deleting_user() {
		$user_id = $this->get_random_user_id();
		$user    = User::find( $user_id );

		$this->assertTrue( $user->delete() );
	}

	/**
	 * Get a random user ID, ensures the ID is not the last in the set.
	 *
	 * @return integer
	 */
	protected function get_random_user_id( $args = [] ): int {
		$post_ids = static::factory()->user->create_many( 11, $args );
		array_pop( $post_ids );
		return $post_ids[ array_rand( $post_ids ) ];
	}
}
