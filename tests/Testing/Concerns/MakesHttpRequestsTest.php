<?php
namespace Mantle\Tests\Testing\Concerns;

use JsonSerializable;
use Mantle\Facade\Route;
use Mantle\Http\Response;
use Mantle\Framework\Providers\Routing_Service_Provider;
use Mantle\Http\Request;
use Mantle\Testing\Concerns\Refresh_Database;
use Mantle\Testing\Concerns\Reset_Server;
use Mantle\Testing\Framework_Test_Case;
use Mantle\Testing\Test_Response;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\Group;
use WP_REST_Response;

use function Mantle\Support\Helpers\collect;

/**
 * @group testing
 */
#[Group( 'testing' )]
class MakesHttpRequestsTest extends Framework_Test_Case {
	use Refresh_Database;
	use Reset_Server;

	protected function setUp(): void {
		parent::setUp();

		putenv( 'MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST=' );

		remove_all_actions( 'template_redirect' );

		update_option( 'home', 'http://' . WP_TESTS_DOMAIN );
	}

	public function test_get_home() {
		$this->get( home_url( '/' ) );
		$this->assertQueryTrue( 'is_home', 'is_front_page' );
	}

	public function test_get_singular() {
		$post_id = static::factory()->post->create();
		$this->get( get_permalink( $post_id ) )
			->assertQueryTrue( 'is_single', 'is_singular' )
			->assertQueriedObjectId( $post_id )
			->assertSee( get_the_title( $post_id ) );
	}

	public function test_fluent() {
		$_SERVER['__request_headers'] = [];

		add_action(
			'template_redirect',
			function() {
				$_SERVER['__request_headers'] = collect( getallheaders() )->to_array();
			},
		);

		$this->add_default_header( 'x-default', 'default' );

		$this->with_header( 'x-test', 'test' )
			->get( home_url( '/' ) )
			->assertQueryTrue( 'is_home', 'is_front_page' );

		// @phpstan-ignore-next-line
		$this->assertNotEmpty( $_SERVER['__request_headers']['X-Test'] ?? null );
		$this->assertEquals( 'test', $_SERVER['__request_headers']['X-Test'][0] );

		$this->assertNotEmpty( $_SERVER['__request_headers']['X-Default'] );
		$this->assertEquals( 'default', $_SERVER['__request_headers']['X-Default'][0] );

		remove_all_actions( 'template_redirect' );
	}

	public function test_get_term() {
		$category_id = static::factory()->category->create();

		$this->get( get_term_link( $category_id, 'category' ) );
		$this->assertQueryTrue( 'is_archive', 'is_category' );
		$this->assertQueriedObjectId( $category_id );
	}

	public function test_wordpress_404() {
		$this
			->get( '/not-found/should-404/' )
			->assertNotFound();
	}

	/**
	 * Test checking against a Mantle route.
	 */
	public function test_get_mantle_route() {
		$_SERVER['__route_run'] = false;

		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->get(
			'/test-route',
			function() {
				$_SERVER['__route_run'] = true;
				return 'yes';
			}
		);

		$this->get( '/test-route' )
			->assertOk()
			->assertContent( 'yes' );

		$this->assertTrue( $_SERVER['__route_run'] );
	}

	public function test_get_mantle_route_404() {
		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->get(
			'/test-route-404',
			function() {
				return response()->make( 'not-found', 404 );
			}
		);

		$this->get( '/test-route-404' )
			->assertNotFound()
			->assertContent( 'not-found' );
	}

	public function test_post_mantle_route() {
		// Ensure routing is enabled.
		$this->assertNotNull( $this->app->get_provider( Routing_Service_Provider::class ) );

		// Register a route.
		$this->app['router']->post(
			'/test-post',
			function() {
				return new Response( 'yes', 201, [ 'test-header' => 'test-value' ] );
			}
		);

		$this->app['router']->get(
			'/404',
			function() {
				return new Response( 'yes', 404 );
			}
		);

		$this->post( '/test-post' )
			->assertCreated()
			->assertHeader( 'test-header', 'test-value' )
			->assertContent( 'yes' );

		$this->get( '/404' )->assertNotFound();
	}

	public function test_rest_api_route() {
		$post_id = static::factory()->post->create();

		$this->get( rest_url( "wp/v2/posts/{$post_id}" ) )
			->assertOk()
			->assertJsonPath( 'id', $post_id )
			->assertJsonPath( 'title.rendered', get_the_title( $post_id ) )
			->assertJsonPathExists( 'guid' )
			->assertJsonPathMissing( 'example_path' );
	}

	public function test_rest_api_route_headers() {
		$this->ignoreIncorrectUsage();

		register_rest_route(
			'/mantle/v1',
			__FUNCTION__,
			[
				'methods'  => 'GET',
				'validate_callback' => '__return_true',
				'callback' => fn () => new WP_REST_Response( [ 'key' => 'value here' ], 201, [ 'test-header' => 'test-value' ] ),
			]
		);

		$this->get( rest_url( '/mantle/v1/' . __FUNCTION__ ) )
			->assertStatus( 201 )
			->assertHeader( 'test-header', 'test-value' )
			->assertIsJson()
			->assertJsonPath( 'key', 'value here' );
	}

	public function test_rest_api_route_error() {
		$this->get( rest_url( '/an/unknown/route' ) )
			->assertStatus( 404 )
			->assertNotFound();
	}

	public function test_redirect_response() {
		$this->app['router']->get(
			'/route-to-redirect/',
			fn () => redirect()->to( '/redirected/', 302, [ 'Other-Header' => '123' ] ),
		);

		$this->get( '/route-to-redirect/' )
			->assertHeader( 'location', home_url( '/redirected/' ) )
			->assertHeader( 'Location', home_url( '/redirected/' ) )
			->assertRedirect( '/redirected/' )
			->assertHeader( 'Other-Header', '123' );
	}

	public function test_redirect_wp_redirect() {
		add_action(
			'template_redirect',
			function () {
				wp_redirect( home_url( '/redirected/' ), 302 );
			},
		);

		$this->get( '/' )->assertRedirect( home_url( '/redirected/' ) );

		remove_all_actions( 'template_redirect' );
	}

	public function test_post_json_mantle_route() {
		$this->app['router']->post(
			'/test-post-json',
			fn ( Request $request ) => new Response( $request['foo'], 201, [ 'test-header' => 'test-value' ] ),
		);

		$this->post_json( '/test-post-json', [ 'foo' => 'bar' ] )
			->assertCreated()
			->assertIsNotJson()
			->assertContent( 'bar' );
	}

	public function test_post_json_wordpress_route() {
		$this->ignoreIncorrectUsage();

		register_rest_route(
			'/mantle/v1',
			__FUNCTION__,
			[
				'methods' => 'POST',
				'validate_callback' => '__return_true',
				'callback' => fn ( \WP_REST_Request $request ) => $request['foo'] ?? 'no foo',
			]
		);

		$this->post_json( rest_url( '/mantle/v1/' . __FUNCTION__ ), [ 'foo' => 'bar' ] )
			->assertContent( '"bar"' );
	}

	public function test_assert_json_structure() {
		$response = Test_Response::from_base_response(
			new Response( new JsonSerializableMixedResourcesStub() )
		);

		// Without structure
		$response->assertJsonStructure();

		// At root
		$response->assertJsonStructure( [ 'foo' ] );

		// Nested
		$response->assertJsonStructure( [ 'foobar' => [ 'foobar_foo', 'foobar_bar' ] ]);

		// Wildcard (repeating structure)
		$response->assertJsonStructure( [ 'bars' => [ '*' => [ 'bar', 'foo' ] ] ] );

		// Wildcard (numeric keys)
		$response->assertJsonStructure( [ 'numeric_keys' => [ '*' => ['bar', 'foo' ] ] ] );

		// Nested after wildcard
		$response->assertJsonStructure( [ 'baz' => [ '*' => [ 'foo', 'bar' => [ 'foo', 'bar' ] ] ] ] );
	}

	public function test_callbacks() {
		$_SERVER['__callback_before'] = false;
		$_SERVER['__callback_after']  = false;

		$this
			->before_request( fn () => $_SERVER['__callback_before'] = true )
			->after_request( fn ( $response ) => $_SERVER['__callback_after'] = $response )
			->get( '/' );

		$this->assertTrue( $_SERVER['__callback_before'] );
		$this->assertInstanceOf( Test_Response::class, $_SERVER['__callback_after'] );
	}

	public function test_match_snapshot() {
		$this->assertMatchesSnapshot( [ 1, 2, 3 ] );
	}

	public function test_match_snapshot_http() {
		Route::get( '/example/', fn () => 'example' );

		$this->get( '/example' )->assertMatchesSnapshotContent();
	}

	public function test_match_snapshot_http_partial() {
		$random = wp_rand();

		Route::get( '/example/', fn () => <<<HTML
			<!DOCTYPE html>
			<html lang="en">
				<head>
					<meta charset="UTF-8">
					<title>Example</title>
				</head>
				<body>
					<div class="selector-ignored">
						<p>Another selector ignored</p>
						{$random}
					</div>
					<div class="example">
						<p>Example</p>
					</div>
					<div class="example-two">
						<p>Example two</p>
					</div>
				</body>
			</html>
			HTML
		);

		$this->get( '/example' )->assertMatchesSnapshotHtml( [
			'/html/body/div[@class="example"]',
			'/html/body/div[@class="example-two"]',
		] );
	}

	public function test_match_snapshot_no_selectors_matched() {
		$this->expectException( AssertionFailedError::class );

		Route::get( '/example/', fn () => 'example' );

		$this->get( '/example' )->assertMatchesSnapshotHtml( [
			'/html/body/div[@class="example"]',
		] );
	}

	public function test_wp_is_rest_endpoint() {
		$this->ignoreIncorrectUsage();

		if ( ! function_exists( 'wp_is_rest_endpoint' ) ) {
			$this->markTestSkipped( 'wp_is_rest_endpoint() is not available.' );
		}

		$this->assertFalse( wp_is_rest_endpoint() );

		register_rest_route(
			'mantle/v1',
			__FUNCTION__,
			[
				'methods' => 'GET',
				'validate_callback' => '__return_true',
				'callback' => function () {
					$this->assertTrue( wp_is_rest_endpoint() );

					return [ 'key' => 'value here' ];
				},
			]
		);

		$this
			->get( rest_url( '/mantle/v1/' . __FUNCTION__ ) )
			->assertJsonPath( 'key', 'value here' );

		$this->assertFalse( wp_is_rest_endpoint() );
	}

	public function test_match_snapshot_rest() {
		$this->ignoreIncorrectUsage();

		register_rest_route(
			'mantle/v1',
			__FUNCTION__,
			[
				'methods' => 'GET',
				'validate_callback' => '__return_true',
				'callback' => fn () => new WP_REST_Response( [ 'key' => 'value here' ], 201, [ 'test-header' => 'test-value' ] ),
			]
		);

		$this->get( rest_url( '/mantle/v1/' . __FUNCTION__ ) )->assertMatchesSnapshotContent();

		$this->get( rest_url( '/wp/v2/posts/' . static::factory()->post->create() ) )->assertMatchesSnapshotJson( 'type' );

		static::factory()->post->create_many( 10 );

		$this
			->get( rest_url( '/wp/v2/posts' ) )
			->assertMatchesSnapshotJson( [
				'*.type',
				'*.status',
			] );
	}

	public function test_url_scheme_http_by_default() {
		$this->get( '/' )->assertOk();

		$this->assertEmpty( $_SERVER['HTTPS'] ?? '' );
	}

	public function test_url_scheme_https_opt_in() {
		$this->get( '/' )->assertOk();

		$this->assertEmpty( $_SERVER['HTTPS'] ?? '' );

		$this->with_https()->get( 'https://example.com' )->assertOk();

		$this->assertEquals( 'on', $_SERVER['HTTPS'] );
	}

	public function test_url_scheme_https_by_home_url() {
		putenv( 'MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST=1' );

		$home_url = get_option( 'home' );

		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN, $home_url );
		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN, home_url() );

		update_option( 'home', 'https://' . WP_TESTS_DOMAIN );

		$this->assertEquals( 'https://' . WP_TESTS_DOMAIN, home_url() );

		$this->get( '/' )->assertOk();

		$this->assertEquals( 'on', $_SERVER['HTTPS'] ?? '' );
	}

	#[Group( 'experimental' )]
	#[Group( 'experiment-testing-url-host' )]
	public function test_experimental_default_url_host() {
		$this->get( '/' )->assertOk();

		$this->assertEquals( 'http://' . WP_TESTS_DOMAIN, home_url() );
		$this->assertEquals( WP_TESTS_DOMAIN, $_SERVER['HTTP_HOST'] );

		$this->setup_experiment_testing_url_host();

		$this->get( '/' )->assertOk();

		$this->assertEquals( 'subdomain.' . WP_TESTS_DOMAIN, $_SERVER['HTTP_HOST'] );
	}

	#[Group( 'experimental' )]
	#[Group( 'experiment-testing-url-host' )]
	public function test_experimental_redirect_to() {
		$this->setup_experiment_testing_url_host();

		$this->app['router']->get(
			'/route-to-redirect/',
			fn () => redirect()->to( '/redirected/' ),
		);

		$this->get( '/route-to-redirect/' )->assertRedirect( '/redirected/' );
	}

	public function test_multiple_requests() {
		$methods = collect( get_class_methods( $this ) )
			->filter( fn ( $method ) => false === strpos( $method, '_snapshot_' ) )
			->shuffle()
			->all();

		// Re-run all test methods on this class in a single pass.
		foreach ( $methods as $method ) {
			if ( __FUNCTION__ === $method || 'test_' !== substr( $method, 0, 5 ) ) {
				continue;
			}

			$this->setUp();

			$this->$method();

			$this->tearDown();
		}
	}

	protected function setup_experiment_testing_url_host() {
		putenv( 'MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST=1' );

		update_option( 'home', 'https://subdomain.' . WP_TESTS_DOMAIN );
		$this->assertEquals( 'https://subdomain.' . WP_TESTS_DOMAIN, home_url() );
	}
}

class JsonSerializableMixedResourcesStub implements JsonSerializable {
	public function jsonSerialize(): array {
		return [
			'foo'          => 'bar',
			'foobar'       => [
				'foobar_foo' => 'foo',
				'foobar_bar' => 'bar',
			],
			'0'            => [ 'foo' ],
			'bars'         => [
				[
					'bar' => 'foo 0',
					'foo' => 'bar 0',
				],
				[
					'bar' => 'foo 1',
					'foo' => 'bar 1',
				],
				[
					'bar' => 'foo 2',
					'foo' => 'bar 2',
				],
			],
			'baz'          => [
				[
					'foo' => 'bar 0',
					'bar' => [
						'foo' => 'bar 0',
						'bar' => 'foo 0',
					],
				],
				[
					'foo' => 'bar 1',
					'bar' => [
						'foo' => 'bar 1',
						'bar' => 'foo 1',
					],
				],
			],
			'barfoo'       => [
				[ 'bar' => [ 'bar' => 'foo 0' ] ],
				[
					'bar' => [
						'bar' => 'foo 0',
						'foo' => 'foo 0',
					],
				],
				[
					'bar' => [
						'foo' => 'bar 0',
						'bar' => 'foo 0',
						'rab' => 'rab 0',
					],
				],
			],
			'numeric_keys' => [
				2 => [
					'bar' => 'foo 0',
					'foo' => 'bar 0',
				],
				3 => [
					'bar' => 'foo 1',
					'foo' => 'bar 1',
				],
				4 => [
					'bar' => 'foo 2',
					'foo' => 'bar 2',
				],
			],
		];
	}
}
