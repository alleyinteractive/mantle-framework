<?php //phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
/**
 * This file contains the Assertions trait
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Database\Model\Post;
use Mantle\Database\Model\Term;
use Mantle\Database\Model\User;
use PHPUnit\Framework\Assert as PHPUnit;
use WP_Term;

use function Mantle\Support\Helpers\get_term_object;

/**
 * Assorted Test_Cast assertions.
 */
trait Assertions {
	use Asset_Assertions;

	/**
	 * Asserts that the given value is an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public static function assertWPError( $actual, $message = '' ) {
		PHPUnit::assertInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given value is not an instance of WP_Error.
	 *
	 * @param mixed  $actual  The value to check.
	 * @param string $message Optional. Message to display when the assertion fails.
	 */
	public static function assertNotWPError( $actual, $message = '' ) {
		if ( '' === $message && is_wp_error( $actual ) ) {
			$message = $actual->get_error_message();
		}
		PHPUnit::assertNotInstanceOf( 'WP_Error', $actual, $message );
	}

	/**
	 * Asserts that the given fields are present in the given object.
	 *
	 * @param object $object The object to check.
	 * @param array  $fields The fields to check.
	 */
	public static function assertEqualFields( $object, $fields ) {
		foreach ( $fields as $field_name => $field_value ) {
			if ( $object->$field_name !== $field_value ) {
				PHPUnit::fail();
			}
		}
	}

	/**
	 * Asserts that two values are equal, with whitespace differences discarded.
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public static function assertDiscardWhitespace( $expected, $actual ) {
		PHPUnit::assertEquals( preg_replace( '/\s*/', '', $expected ), preg_replace( '/\s*/', '', $actual ) );
	}

	/**
	 * Asserts that two values are equal, with EOL differences discarded.
	 *
	 * @param string $expected The expected value.
	 * @param string $actual   The actual value.
	 */
	public static function assertEqualsIgnoreEOL( $expected, $actual ) {
		PHPUnit::assertEquals( str_replace( "\r\n", "\n", $expected ), str_replace( "\r\n", "\n", $actual ) );
	}

	/**
	 * Asserts that the contents of two un-keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public static function assertEqualSets( $expected, $actual ) {
		sort( $expected );
		sort( $actual );
		PHPUnit::assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the contents of two keyed, single arrays are equal, without accounting for the order of elements.
	 *
	 * @param array $expected Expected array.
	 * @param array $actual   Array to check.
	 */
	public static function assertEqualSetsWithIndex( $expected, $actual ) {
		ksort( $expected );
		ksort( $actual );
		PHPUnit::assertEquals( $expected, $actual );
	}

	/**
	 * Asserts that the given variable is a multidimensional array, and that all arrays are non-empty.
	 *
	 * @param array $array Array to check.
	 */
	public static function assertNonEmptyMultidimensionalArray( $array ) {
		PHPUnit::assertTrue( is_array( $array ) );
		PHPUnit::assertNotEmpty( $array );

		foreach ( $array as $sub_array ) {
			PHPUnit::assertTrue( is_array( $sub_array ) );
			PHPUnit::assertNotEmpty( $sub_array );
		}
	}

	/**
	 * Checks each of the WP_Query is_* functions/properties against expected
	 * boolean value.
	 *
	 * Any properties that are listed by name as parameters will be expected to be
	 * true; all others are expected to be false. For example,
	 * assertQueryTrue( 'is_single', 'is_feed' ) means is_single() and is_feed()
	 * must be true and everything else must be false to pass.
	 *
	 * @param string ...$prop Any number of WP_Query properties that are expected
	 *                        to be true for the current request.
	 */
	public static function assertQueryTrue( ...$prop ) {
		global $wp_query;

		$all = [
			'is_404',
			'is_admin',
			'is_archive',
			'is_attachment',
			'is_author',
			'is_category',
			'is_comment_feed',
			'is_date',
			'is_day',
			'is_embed',
			'is_feed',
			'is_front_page',
			'is_home',
			'is_privacy_policy',
			'is_month',
			'is_page',
			'is_paged',
			'is_post_type_archive',
			'is_posts_page',
			'is_preview',
			'is_robots',
			'is_favicon',
			'is_search',
			'is_single',
			'is_singular',
			'is_tag',
			'is_tax',
			'is_time',
			'is_trackback',
			'is_year',
		];

		foreach ( $prop as $true_thing ) {
			PHPUnit::assertContains( $true_thing, $all, "Unknown conditional: {$true_thing}." );
		}

		$passed  = true;
		$message = '';

		foreach ( $all as $query_thing ) {
			$result = is_callable( $query_thing ) ? call_user_func( $query_thing ) : $wp_query->$query_thing;

			if ( in_array( $query_thing, $prop, true ) ) {
				if ( ! $result ) {
					$message .= $query_thing . ' is false but is expected to be true. ' . PHP_EOL;
					$passed   = false;
				}
			} elseif ( $result ) {
				$message .= $query_thing . ' is true but is expected to be false. ' . PHP_EOL;
				$passed   = false;
			}
		}

		if ( ! $passed ) {
			PHPUnit::fail( $message );
		}
	}

	/**
	 * Assert that a given ID matches the global queried object ID.
	 *
	 * @param int $id Expected ID.
	 */
	public static function assertQueriedObjectId( int $id ) {
		PHPUnit::assertSame( $id, get_queried_object_id() );
	}

	/**
	 * Assert that a given object is equivalent to the global queried object.
	 *
	 * @param Object $object Expected object.
	 */
	public static function assertQueriedObject( $object ) {
		global $wp_query;
		$queried_object = $wp_query->get_queried_object();

		// First, assert the same object types.
		PHPUnit::assertInstanceOf( get_class( $object ), $queried_object );

		// Next, assert identifying data about the object.
		switch ( true ) {
			case $object instanceof Post:
			case $object instanceof User:
				PHPUnit::assertSame( $object->id(), $queried_object->ID );
				break;

			case $object instanceof Term:
				PHPUnit::assertSame( $object->id(), $queried_object->term_id );
				break;

			case $object instanceof \WP_Post:
			case $object instanceof \WP_User:
				PHPUnit::assertSame( $object->ID, $queried_object->ID );
				break;

			case $object instanceof \WP_Term:
				PHPUnit::assertSame( $object->term_id, $queried_object->term_id );
				break;

			case $object instanceof \WP_Post_Type:
				PHPUnit::assertSame( $object->name, $queried_object->name );
				break;
		}
	}

	/**
	 * Assert if a post exists given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertPostExists( array $arguments ) {
		$posts = \get_posts(
			array_merge(
				[
					'fields'         => 'ids',
					'posts_per_page' => 1,
				],
				$arguments
			)
		);

		PHPUnit::assertNotEmpty( $posts );
	}

	/**
	 * Assert if a post does not exist given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertPostDoesNotExists( array $arguments ) {
		$posts = \get_posts(
			array_merge(
				[
					'fields'         => 'ids',
					'posts_per_page' => 1,
				],
				$arguments
			)
		);

		PHPUnit::assertEmpty( $posts );
	}

	/**
	 * Assert if a term exists given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertTermExists( array $arguments ) {
		$terms = \get_terms(
			array_merge(
				[
					'fields'     => 'ids',
					'count'      => 1,
					'hide_empty' => false,
				],
				$arguments
			)
		);

		PHPUnit::assertNotEmpty( $terms );
	}

	/**
	 * Assert if a term does not exist given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertTermDoesNotExists( array $arguments ) {
		$terms = \get_terms(
			array_merge(
				[
					'fields'     => 'ids',
					'count'      => 1,
					'hide_empty' => false,
				],
				$arguments
			)
		);

		PHPUnit::assertEmpty( $terms );
	}

	/**
	 * Assert if a user exists given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertUserExists( array $arguments ) {
		$users = \get_users(
			array_merge(
				[
					'fields' => 'ids',
					'count'  => 1,
				],
				$arguments
			)
		);

		PHPUnit::assertNotEmpty( $users );
	}

	/**
	 * Assert if a user does not exist given a set of arguments.
	 *
	 * @param array $arguments Arguments to query against.
	 */
	public function assertUserDoesNotExists( array $arguments ) {
		$users = \get_users(
			array_merge(
				[
					'fields' => 'ids',
					'count'  => 1,
				],
				$arguments
			)
		);

		PHPUnit::assertEmpty( $users );
	}

	/**
	 * Get a term object from a flexible argument.
	 *
	 * @param Term|\WP_Term|int $argument Term object, term ID, or term slug.
	 * @return WP_Term|null
	 */
	protected function get_term_from_argument( $argument ): ?WP_Term {
		if ( $argument instanceof Term ) {
			return $argument->core_object();
		}

		if ( is_int( $argument ) ) {
			return get_term_object( $argument );
		}

		if ( $argument instanceof WP_Term ) {
			return $argument;
		}

		return null;
	}

	/**
	 * Assert that a post has a specific term.
	 *
	 * `assertPostNotHasTerm()` is the inverse of this method.
	 *
	 * @param Post|\WP_Post|int $post Post to check.
	 * @param Term|\WP_Term|int $term Term to check.
	 * @return void
	 */
	public function assertPostHasTerm( $post, $term ) {
		if ( $post instanceof Post ) {
			$post = $post->id();
		}

		$term = $this->get_term_from_argument( $term );

		PHPUnit::assertInstanceOf( \WP_Term::class, $term, 'Term not found to assert against' );
		PHPUnit::assertTrue( \has_term( $term->term_id, $term->taxonomy, $post ) );
	}

	/**
	 * Assert that a post doesn't have a specific term.
	 *
	 * `assertPostHasTerm()` is the inverse of this method.
	 *
	 * @param Post|\WP_Post|int $post Post to check.
	 * @param Term|\WP_Term|int $term Term to check.
	 * @return void
	 */
	public function assertPostNotHasTerm( $post, $term ) {
		if ( $post instanceof Post ) {
			$post = $post->id();
		}

		$term = $this->get_term_from_argument( $term );

		if ( $term ) {
			PHPUnit::assertFalse( \has_term( $term->term_id, $term->taxonomy, $post ) );
		}
	}

	/**
	 * Alias of `assertPostNotHasTerm()`.
	 *
	 * @param Post|\WP_Post|int $post Post to check.
	 * @param Term|\WP_Term|int $term Term to check.
	 */
	public function assertPostsDoesNotHaveTerm( $post, $term ) {
		$this->assertPostNotHasTerm( $post, $term );
	}
}
