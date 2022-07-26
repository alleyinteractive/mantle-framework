<?php
/**
 * This file contains the Makes_Http_Requests trait
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Database\Model\Model;
use Mantle\Framework\Http\Kernel as HttpKernel;
use Mantle\Http\Request;
use Mantle\Support\Str;
use Mantle\Testing\Exceptions\Exception;
use Mantle\Testing\Exceptions\WP_Redirect_Exception;
use Mantle\Testing\Test_Response;
use Mantle\Testing\Utils;
use WP;
use WP_Query;

/**
 * Trait for Test_Case classes which want to make http-like requests against
 * WordPress.
 */
trait Makes_Http_Requests {
	/**
	 * Additional headers for the request.
	 *
	 * @var array
	 */
	protected array $default_headers = [];

	/**
	 * Additional cookies for the request.
	 *
	 * @var array
	 */
	protected array $default_cookies = [];

	/**
	 * Indicates whether redirects should be followed.
	 *
	 * @var bool
	 */
	protected $follow_redirects = false;

	/**
	 * Store flag if the request was for the REST API.
	 *
	 * @var string|bool
	 */
	protected $rest_api_response = false;

	/**
	 * The array of callbacks to be run before the event is started.
	 *
	 * @var array
	 */
	protected $before_callbacks = [];

	/**
	 * The array of callbacks to be run after the event is finished.
	 *
	 * @var array
	 */
	protected $after_callbacks = [];

	/**
	 * Setup the trait in the test case.
	 */
	public function makes_http_requests_set_up() {
		global $wp_rest_server, $wp_actions;

		// Clear out the existing REST Server to allow for REST API routes to be re-registered.
		$wp_rest_server = null; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals

		// Mark 'rest_api_init' as an un-run action.
		unset( $wp_actions['rest_api_init'] );

		// Clear before/after callbacks.
		$this->before_callbacks = [];
		$this->after_callbacks  = [];
	}

	/**
	 * Define additional headers to be sent with the request.
	 *
	 * @param array $headers Headers for the request.
	 * @return $this
	 */
	public function with_headers( array $headers ) {
		$this->default_headers = array_merge( $this->default_headers, $headers );

		return $this;
	}

	/**
	 * Flush all the configured headers.
	 *
	 * @return $this
	 */
	public function flush_headers() {
		$this->default_headers = [];

		return $this;
	}

	/**
	 * Make a request with a set of cookies.
	 *
	 * @param array $cookies Cookies to be sent with the request.
	 * @return static
	 */
	public function with_cookies( array $cookies ) {
		$this->default_cookies = array_merge( $this->default_cookies, $cookies );

		return $this;
	}

	/**
	 * Make a request with a specific cookie.
	 *
	 * @param string $name  Cookie name.
	 * @param string $value Cookie value.
	 * @return static
	 */
	public function with_cookie( string $name, string $value ) {
		$this->default_cookies[ $name ] = $value;

		return $this;
	}

	/**
	 * Flush the cookies for the request.
	 *
	 * @return static
	 */
	public function flush_cookies() {
		$this->default_cookies = [];

		return $this;
	}

	/**
	 * Automatically follow any redirects returned from the response.
	 *
	 * @return $this
	 */
	public function following_redirects() {
		$this->follow_redirects = true;

		return $this;
	}

	/**
	 * Set the referer header and previous URL session value in order to simulate
	 * a previous request.
	 *
	 * @param string $url URL for the referer header.
	 * @return $this
	 */
	public function from( string $url ) {
		return $this->with_header( 'referer', $url );
	}

	/**
	 * Add a header to be sent with the request.
	 *
	 * @param string $name  Header name (key).
	 * @param string $value Header value.
	 * @return $this
	 */
	public function with_header( string $name, string $value ) {
		$this->default_headers[ $name ] = $value;

		return $this;
	}

	/**
	 * Visit the given URI with a GET request.
	 *
	 * @param mixed $uri     Request URI.
	 * @param array $headers Request headers.
	 * @return Test_Response
	 */
	public function get( $uri, array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'GET', $uri, [], $server );
	}

	/**
	 * Legacy support for the WordPress core unit test's `go_to()` method.
	 *
	 * @deprecated Use {@see Mantle\Testing\Concerns\Makes_Http_Requests::get()} instead.
	 * @param string $url The URL for the request.
	 */
	public function go_to( string $url ) {
		$this->get( $url );
	}

	/**
	 * Infer the request URL from an object like a post or term.
	 *
	 * @param mixed $source Source from which to infer the URL.
	 * @return string
	 */
	protected function infer_url( $source ): string {
		switch ( true ) {
			case $source instanceof \WP_Post:
				return get_permalink( $source );

			case $source instanceof \WP_Term:
				return get_term_link( $source );

			case $source instanceof \WP_User:
				return \get_author_posts_url( $source->ID );

			case $source instanceof Model && method_exists( $source, 'permalink' ):
				return $source->permalink();
		}

		return '';
	}

	/**
	 * Transform headers array to array of $_SERVER vars with HTTP_* format.
	 *
	 * @param array $headers Headers to convert to $_SERVER vars.
	 * @return array
	 */
	protected function transform_headers_to_server_vars( array $headers ) {
		$headers           = array_merge( $this->default_headers, $headers );
		$formatted_headers = [];
		foreach ( $headers as $name => $value ) {
			$name = strtr( strtoupper( $name ), '-', '_' );

			$formatted_headers[ $this->format_server_header_key( $name ) ] = $value;
		}

		return $formatted_headers;
	}

	/**
	 * Format the header name for the server array.
	 *
	 * @param string $name Header name.
	 * @return string
	 */
	protected function format_server_header_key( $name ) {
		if ( ! Str::starts_with( $name, 'HTTP_' ) && 'CONTENT_TYPE' !== $name && 'REMOTE_ADDR' !== $name ) {
			return 'HTTP_' . $name;
		}

		return $name;
	}

	/**
	 * Call the given URI and return the Response.
	 *
	 * @param string $method     Request method.
	 * @param string $uri        Request URI.
	 * @param array  $parameters Request params.
	 * @param array  $server     Server vars.
	 * @param array  $cookies Cookies to be sent with the request.
	 * @return Test_Response
	 */
	public function call( $method, $uri, $parameters = [], $server = [], array $cookies = [] ) {
		$this->reset_request_state();

		if ( ! is_string( $uri ) ) {
			$uri = $this->infer_url( $uri );
		}

		// Build a full URL from partial URIs.
		if ( '/' === $uri[0] ) {
			$url = 'https://' . WP_TESTS_DOMAIN . $uri;
		} elseif ( false === strpos( $uri, '://' ) ) {
			$url = 'https://' . WP_TESTS_DOMAIN . '/' . $uri;
		} else {
			$url = $uri;
		}

		$this->set_server_state(
			$method,
			$url,
			$server,
			$parameters,
			array_merge( $this->default_cookies, $cookies ),
		);

		$response_status  = null;
		$response_headers = [];

		$intercept_status = function( $status_header, $code ) use ( &$response_status ) {
			if ( ! $response_status ) {
				$response_status = $code;
			}
		};

		$intercept_headers = function( $send_headers ) use ( &$response_headers ) {
			$response_headers = $send_headers;
		};

		$intercept_redirect = function( $location, $status ) use ( &$response_status, &$response_headers ) {
			$response_status              = $status;
			$response_headers['Location'] = $location;
			throw new WP_Redirect_Exception();
		};

		add_filter( 'exit_on_http_head', '__return_false', 9999 );
		add_filter( 'wp_using_themes', '__return_true', 9999 );

		$this->call_before_callbacks();

		// Attempt to run the query through the Mantle router.
		if ( isset( $this->app['router'] ) ) {
			$kernel = new HttpKernel( $this->app, $this->app['router'] );

			// Setup the current request object.
			$request = Request::capture();
			$this->app->instance( 'request', $request );

			$response = $kernel->send_request_through_router( $request );

			if ( $response ) {
				$response = new Test_Response(
					$response->getContent(),
					$response->getStatusCode(),
					$response->headers->all()
				);
			}
		}

		// Attempt to run the query through the Mantle router.
		if ( empty( $response ) ) {
			add_filter( 'status_header', $intercept_status, 9999, 2 );
			add_filter( 'wp_headers', $intercept_headers, 9999 );
			add_filter( 'wp_redirect', $intercept_redirect, 9999, 2 );

			ob_start();

			$this->setup_wordpress_query();

			if ( $this->rest_api_response ) {
				// Use the response from the REST API server.
				ob_end_clean();

				$response_content = $this->rest_api_response;
			} else {
				try {
					// Execute the request, inasmuch as WordPress would.
					require ABSPATH . WPINC . '/template-loader.php';
				} catch ( Exception $e ) { // phpcs:ignore
					// Mantle Exceptions are thrown to prevent some code from running, e.g.
					// the tail end of wp_redirect().
				}

				$response_content = ob_get_clean();
			}

			remove_filter( 'status_header', $intercept_status, 9999 );
			remove_filter( 'wp_headers', $intercept_headers, 9999 );
			remove_filter( 'wp_redirect', $intercept_redirect, 9999 );

			$response = new Test_Response( $response_content, $response_status ?? 200, $response_headers );
		}

		$response->set_app( $this->app );

		$this->call_after_callbacks( $response );

		remove_filter( 'exit_on_http_head', '__return_false', 9999 );
		remove_filter( 'wp_using_themes', '__return_true', 9999 );

		if ( $this->follow_redirects ) {
			return $this->follow_redirects( $response );
		}

		return $response;
	}

	/**
	 * Reset the global state related to requests.
	 */
	protected function reset_request_state() {
		// phpcs:disable

		/*
		 * Note: the WP and WP_Query classes like to silently fetch parameters
		 * from all over the place (globals, GET, etc), which makes it tricky
		 * to run them more than once without very carefully clearing everything.
		 */
		$_GET    = [];
		$_POST   = [];
		$_COOKIE = [];
		foreach (
			[
				'query_string',
				'id',
				'postdata',
				'authordata',
				'day',
				'currentmonth',
				'page',
				'pages',
				'multipage',
				'more',
				'numpages',
				'pagenow',
				'current_screen',
			] as $v
		) {
			if ( isset( $GLOBALS[ $v ] ) ) {
				unset( $GLOBALS[ $v ] );
			}
		}

		$this->rest_api_response = false;

		// phpcs:enable
	}

	/**
	 * Set $_SERVER keys for the request.
	 *
	 * @param string $method HTTP method.
	 * @param string $url    Request URI.
	 * @param array  $server Additional $_SERVER args to set.
	 * @param array  $data   POST data to set.
	 * @param array  $cookies Cookies to be sent with the request.
	 */
	protected function set_server_state( $method, $url, $server, $data, array $cookies = [] ) {
		// phpcs:disable WordPress.Security.NonceVerification
		$_SERVER['REQUEST_METHOD'] = strtoupper( $method );
		$_SERVER['SERVER_NAME']    = WP_TESTS_DOMAIN;
		$_SERVER['SERVER_PORT']    = '80';
		unset( $_SERVER['PATH_INFO'] );

		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = isset( $parts['path'] ) ? $parts['path'] : '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				parse_str( $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}
		$_SERVER['REQUEST_URI'] = $req;

		$_POST = $data;

		// The ini setting variable_order determines order; assume GP for simplicity.
		$_REQUEST = array_merge( $_GET, $_POST );
		$_SERVER  = array_merge( $_SERVER, $server );

		// Set the cookies for the request.
		$_COOKIE = $cookies; // phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE

		// phpcs:enable
	}

	/**
	 * Sets the WordPress query as if a given URL has been requested.
	 *
	 * This sets:
	 * - The super globals.
	 * - The globals.
	 * - The query variables.
	 * - The main query.
	 */
	protected function setup_wordpress_query() {
		self::flush_cache();

		// phpcs:disable WordPress.WP.GlobalVariablesOverride
		unset( $GLOBALS['wp_query'], $GLOBALS['wp_the_query'] );
		$GLOBALS['wp_the_query'] = new WP_Query();
		$GLOBALS['wp_query']     = $GLOBALS['wp_the_query'];

		$public_query_vars  = $GLOBALS['wp']->public_query_vars;
		$private_query_vars = $GLOBALS['wp']->private_query_vars;

		$GLOBALS['wp']                     = new WP();
		$GLOBALS['wp']->public_query_vars  = $public_query_vars;
		$GLOBALS['wp']->private_query_vars = $private_query_vars;

		Utils::cleanup_query_vars();

		$this->replace_rest_api();

		$GLOBALS['wp']->main();

		// phpcs:enable WordPress.WP.GlobalVariablesOverride
	}

	/**
	 * Replace the REST API request.
	 *
	 * This will:
	 * - Initiate the REST API.
	 * - Set the WordPress REST Server to use the Mantle Spy REST Server to allow
	 *   for the responses to be read.
	 * - Replace the REST API `rest_api_loaded` method to allow the REST response
	 *   to be read without terminating the script.
	 */
	protected function replace_rest_api() {
		// Ensure the Mantle REST Spy Server is used.
		add_filter( 'wp_rest_server_class', [ Utils::class, 'wp_rest_server_class_filter' ], PHP_INT_MAX );

		rest_api_init();

		// Replace the `rest_api_loaded()` method with one we can control.
		remove_filter( 'parse_request', 'rest_api_loaded' );
		add_action( 'parse_request', [ $this, 'serve_rest_api_request' ] );
	}

	/**
	 * Server the REST API request if applicable.
	 *
	 * Mirroring `{@see rest_api_loaded()}`, this method fires the REST API
	 * request and stores the response.
	 */
	public function serve_rest_api_request() {
		if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return;
		}

		// Initialize the server.
		$server = rest_get_server();
		$route  = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );
		if ( empty( $route ) ) {
			$route = '/';
		}

		$server->serve_request( $route );

		if ( isset( $server->sent_body ) ) {
			$this->rest_api_response = $server->sent_body;
		}
	}

	/**
	 * Turn the given URI into a fully qualified URL.
	 *
	 * @param string $uri URI to fully-qualify.
	 * @return string
	 */
	protected function prepare_url_for_request( $uri ) {
		return trailingslashit( home_url( $uri ) );
	}

	/**
	 * Follow a redirect chain until a non-redirect is received.
	 *
	 * @param Test_Response $response Test response.
	 * @return Test_Response
	 */
	protected function follow_redirects( $response ) {
		while ( $response->is_redirect() ) {
			$response = $this->get( $response->get_header( 'Location' ) );
		}

		$this->follow_redirects = false;

		return $response;
	}

	/**
	 * Visit the given URI with a GET request, expecting a JSON response.
	 *
	 * @param string $uri     URI to "get".
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function get_json( $uri, array $headers = [] ) {
		return $this->json( 'GET', $uri, [], $headers );
	}

	/**
	 * Call the given URI with a JSON request.
	 *
	 * @param string $method  Request method.
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function json( $method, $uri, array $data = [], array $headers = [] ) {
		$content = wp_json_encode( $data );

		$headers = array_merge(
			[
				'CONTENT_LENGTH' => mb_strlen( $content, '8bit' ),
				'CONTENT_TYPE'   => 'application/json',
				'Accept'         => 'application/json',
			],
			$headers
		);

		return $this->call( $method, $uri, [], $this->transform_headers_to_server_vars( $headers ), $content );
	}

	/**
	 * Visit the given URI with a POST request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function post( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'POST', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a POST request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function post_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'POST', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a PUT request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function put( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PUT', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a PUT request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function put_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'PUT', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a PATCH request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function patch( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PATCH', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a PATCH request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function patch_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'PATCH', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a DELETE request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function delete( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'DELETE', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a DELETE request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function delete_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'DELETE', $uri, $data, $headers );
	}

	/**
	 * Visit the given URI with a OPTIONS request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function options( $uri, array $data = [], array $headers = [] ) {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'OPTIONS', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @return Test_Response
	 */
	public function options_json( $uri, array $data = [], array $headers = [] ) {
		return $this->json( 'OPTIONS', $uri, $data, $headers );
	}

	/**
	 * Call a given Closure/method before requests and inject its dependencies.
	 *
	 * @param callable|string $callback Callback to invoke.
	 * @return static
	 */
	public function before_request( $callback ) {
		$this->before_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Call a given Closure/method after requests and inject its dependencies.
	 *
	 * Callback will be invoked with a 'response' argument.
	 *
	 * @param callable|string $callback Callback to invoke.
	 * @return static
	 */
	public function after_request( $callback ) {
		$this->after_callbacks[] = $callback;

		return $this;
	}

	/**
	 * Call all of the "before" callbacks for the requests.
	 */
	protected function call_before_callbacks() {
		foreach ( $this->before_callbacks as $callback ) {
			$this->app->call( $callback );
		}
	}

	/**
	 * Call all of the "after" callbacks for the request.
	 *
	 * @param Test_Response $response Response object.
	 */
	protected function call_after_callbacks( Test_Response $response ) {
		foreach ( $this->after_callbacks as $callback ) {
			$this->app->call(
				$callback,
				[
					'response' => $response,
				]
			);
		}
	}
}
