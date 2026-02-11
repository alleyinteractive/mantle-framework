<?php
/**
 * Pending_Testable_Request class file
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing;

use InvalidArgumentException;
use Mantle\Database\Model\Model;
use Mantle\Framework\Http\Kernel as HttpKernel;
use Mantle\Http\Request;
use Mantle\Support\Str;
use Mantle\Support\Traits\Conditionable;
use Mantle\Testing\Attributes\PreserveObjectCache;
use Mantle\Testing\Doubles\Spy_REST_Server;
use Mantle\Testing\Exceptions\Exception;
use Mantle\Testing\Exceptions\Response_Exception;
use Mantle\Testing\Exceptions\WP_Redirect_Exception;
use Mantle\Testing\TestCase;
use Mantle\Testing\Test_Response;
use Mantle\Testing\Utils;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use WP_Query;
use WP;

/**
 * Pending Testable Request
 *
 * A fluent request that is being made to the application. Performs a SUT
 * (System Under Test) operation on WordPress and returns a response.
 */
class Pending_Testable_Request {
	use Conditionable;

	/**
	 * Indicates whether redirects should be followed.
	 */
	public bool $follow_redirects = false;

	/**
	 * The headers for the request.
	 */
	public HeaderBag $headers;

	/**
	 * Indicates whether the request should be made over HTTPS.
	 */
	public ?bool $forced_https = null;

	/**
	 * The cookies for the request.
	 *
	 * @var InputBag<string|int|float|bool|null>
	 */
	public InputBag $cookies;

	/**
	 * Store flag if the request was for the REST API.
	 *
	 * @var array{body: string, headers: array<string, string>, status: int}|null
	 */
	protected ?array $rest_api_response = null;

	/**
	 * Create a new pending testable request instance.
	 *
	 * @param TestCase $test_case Test case instance.
	 */
	public function __construct( public TestCase $test_case ) {
		$this->headers = new HeaderBag();
		$this->cookies = new InputBag();
	}

	/**
	 * Define additional headers to be sent with the request.
	 *
	 * @param array $headers Headers for the request.
	 */
	public function with_headers( array $headers ): static {
		$this->headers->add( $headers );

		return $this;
	}

	/**
	 * Define additional header to be sent with the request.
	 *
	 * @param string $name  Header name (key).
	 * @param string $value Header value.
	 */
	public function with_header( string $name, string $value ): static {
		return $this->with_headers( [ $name => $value ] );
	}

	/**
	 * Specify the basic authentication username and password for the request.
	 *
	 * @param string $username Username.
	 * @param string $password Password.
	 */
	public function with_basic_auth( string $username, string $password ): static {
		return $this->with_header(
			'Authorization',
			'Basic ' . base64_encode( "{$username}:{$password}" ),
		);
	}

	/**
	 * Specify an authorization token for the request.
	 *
	 * @param  string $token
	 * @param  string $type
	 */
	public function with_token( string $token, string $type = 'Bearer' ): static {
		return $this->with_header( 'Authorization', trim( "{$type} {$token}" ) );
	}

	/**
	 * Define whether the request should be forced to be made over HTTPS.
	 *
	 * This method will override the protocol of the URL passed when creating a
	 * testable request.
	 *
	 * @param bool|null $value Whether to use HTTPS.
	 */
	public function with_https( ?bool $value ): static {
		$this->forced_https = $value;

		return $this;
	}

	/**
	 * Set the referer header and previous URL session value in order to simulate
	 * a previous request.
	 *
	 * @param string $url URL for the referer header.
	 */
	public function from( string $url ): static {
		return $this->with_header( 'referer', $url );
	}

	/**
	 * Make a request with a set of cookies.
	 *
	 * @param array<string, string> $cookies Cookies to be sent with the request.
	 */
	public function with_cookies( array $cookies ): static {
		$this->cookies->add( $cookies );

		return $this;
	}

	/**
	 * Make a request with a specific cookie.
	 *
	 * @param string $name  Cookie name.
	 * @param string $value Cookie value.
	 */
	public function with_cookie( string $name, string $value ): static {
		return $this->with_cookies( [ $name => $value ] );
	}

	/**
	 * Automatically follow any redirects returned from the response.
	 *
	 * @param bool $value Whether to follow redirects.
	 */
	public function following_redirects( bool $value = true ): static {
		$this->follow_redirects = $value;

		return $this;
	}

	/**
	 * Visit the given URI with a GET request.
	 *
	 * @param mixed $uri     Request URI.
	 * @param array $headers Request headers.
	 */
	public function get( $uri, array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'GET', $uri, [], $server );
	}

	/**
	 * Legacy support for the WordPress core unit test's `go_to()` method.
	 *
	 * @deprecated Use {@see Mantle\Testing\Concerns\Makes_Http_Requests::get()} instead.
	 * @param string $url The URL for the request.
	 */
	public function go_to( string $url ): Test_Response {
		return $this->get( $url );
	}

	/**
	 * Infer the request URL from an object like a post or term.
	 *
	 * @throws InvalidArgumentException If the source is not a valid type.
	 *
	 * @param mixed $source Source from which to infer the URL.
	 */
	protected function infer_url( mixed $source ): string {
		return match ( true ) {
			$source instanceof \WP_Post => get_permalink( $source ),
			$source instanceof \WP_Term => get_term_link( $source ),
			$source instanceof \WP_User => \get_author_posts_url( $source->ID ),
			$source instanceof Model && method_exists( $source, 'permalink' ) => $source->permalink(),
			default => throw new InvalidArgumentException(
				'Cannot infer URL from the given source. Expected a WP_Post, WP_Term, WP_User, or a Model with a permalink() method.'
			),
		};
	}

	/**
	 * Transform headers array to array of $_SERVER vars with HTTP_* format.
	 *
	 * @param array $headers Headers to convert to $_SERVER vars.
	 */
	protected function transform_headers_to_server_vars( array $headers ): array {
		$headers           = array_merge( $this->headers->all(), $headers );
		$formatted_headers = [];

		foreach ( $headers as $name => $value ) {
			if ( is_array( $value ) ) {
				$value = end( $value );
			}

			$name = strtr( strtoupper( (string) $name ), '-', '_' );

			$formatted_headers[ $this->format_server_header_key( $name ) ] = $value;
		}

		return $formatted_headers;
	}

	/**
	 * Format the header name for the server array.
	 *
	 * @param string $name Header name.
	 */
	protected function format_server_header_key( string $name ): string {
		if ( ! Str::starts_with( $name, 'HTTP_' ) && 'CONTENT_TYPE' !== $name && 'REMOTE_ADDR' !== $name ) {
			return 'HTTP_' . $name;
		}

		return $name;
	}

	/**
	 * Call the given URI and return the Response.
	 *
	 * @throws \Exception Exceptions thrown while setting up the WordPress query are re-thrown to the caller.
	 * @throws InvalidArgumentException If the request is to an unsupported path.
	 * @throws RuntimeException If the application instance is not available on the test case.
	 *
	 * @param string      $method     Request method.
	 * @param mixed       $uri        Request URI.
	 * @param array       $parameters Request params.
	 * @param array       $server     Server vars.
	 * @param array       $cookies    Cookies to be sent with the request.
	 * @param string|null $content    Request content.
	 */
	public function call( string $method, mixed $uri, array $parameters = [], array $server = [], array $cookies = [], ?string $content = null ): Test_Response {
		$this->reset_request_state();

		if ( ! is_string( $uri ) ) {
			$uri = $this->infer_url( $uri );
		}

		$scheme = $this->get_default_url_scheme();
		$host   = $this->get_default_url_host();

		// Build a full URL from partial URIs.
		if ( '/' === $uri[0] ) {
			$url = "{$scheme}://{$host}{$uri}";
		} elseif ( false === strpos( $uri, '://' ) ) {
			$url = "{$scheme}://{$host}/{$uri}";
		} else {
			$url = $uri;
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		// Check if the user is requesting a call to a path that the testing
		// framework does not support.
		if ( Str::is( [ '/wp-login.php', '/wp-*.php', '/wp-admin/*', '/xmlrpc.php' ], $path ) ) {
			throw new InvalidArgumentException( "Requests to [{$path}] are not supported." );
		}

		$this->set_server_state(
			$method,
			$url,
			$server,
			$parameters,
			array_merge( $this->cookies->all(), $cookies ),
		);

		$response_status  = null;
		$response_headers = [];

		$intercept_status = function ( $status_header, $code ) use ( &$response_status ): int {
			$response_status = $code;

			return $code;
		};

		$intercept_headers = function ( $send_headers ) use ( &$response_headers ): array {
			$response_headers = $send_headers;

			return $send_headers;
		};

		$intercept_redirect = function ( $location, $status ) use ( &$response_status, &$response_headers ): void {
			$response_status              = $status;
			$response_headers['Location'] = $location;
			throw new WP_Redirect_Exception( $status, $location );
		};

		add_filter( 'exit_on_http_head', '__return_false', 9999 );
		add_filter( 'wp_using_themes', '__return_true', 9999 );

		$this->test_case->call_before_callbacks();

		// Setup the current request object.
		$request = new Request(
			$_GET,
			$_POST,
			[],
			$_COOKIE,
			$_FILES,
			$_SERVER,
			$content
		);

		// Attempt to run the query through the Mantle router.
		if ( isset( $this->test_case->app['router'] ) ) {
			$kernel = new HttpKernel( $this->test_case->app, $this->test_case->app['router'] );

			// Mirror the logic from Request::createFromGlobals().
			if (
				str_starts_with( (string) $request->headers->get( 'CONTENT_TYPE', '' ), 'application/x-www-form-urlencoded' )
				&& \in_array( strtoupper( (string) $request->server->get( 'REQUEST_METHOD', 'GET' ) ), [ 'PUT', 'DELETE', 'PATCH' ], true )
			) {
				parse_str( $request->getContent(), $data );

				$request->request = new InputBag( $data ); // @phpstan-ignore-line argument.type
			}

			$this->test_case->app->instance( 'request', $request );

			$response = $kernel->send_request_through_router( $request );

			if ( $response instanceof \Symfony\Component\HttpFoundation\Response ) {
				$response = new Test_Response(
					$response->getContent() ?: null,
					$response->getStatusCode(),
					$response->headers->all(),
					$this->test_case,
				);
			}
		}

		// Attempt to run the query through the Mantle router.
		if ( empty( $response ) ) {
			add_filter( 'status_header', $intercept_status, 9999, 2 );
			add_filter( 'wp_headers', $intercept_headers, 9999 );
			add_filter( 'wp_redirect', $intercept_redirect, 9999, 2 ); // @phpstan-ignore-line Filter callback

			$ob_level   = ob_get_level(); // @phpstan-ignore-line deadCode.unreachable
			$redirected = false;

			ob_start();

			try {
				$this->setup_wordpress_query();
			} catch ( Response_Exception $e ) {
				// Handle a redirect during the early setup of WordPress (parse_query).
				// Prevent an exception from being thrown.
				$response_status  = $e->status;
				$redirected       = true;
				$response_content = ob_get_clean();

				if ( $e instanceof WP_Redirect_Exception ) {
					$response_headers['Location'] = $e->location;
				}

				if ( ! empty( $e->headers ) ) {
					$response_headers = array_merge( $response_headers, $e->headers );
				}
			} catch ( \Exception $e ) {
				// If an exception occurs, make sure the output buffer is closed before
				// the exception continues to the caller.
				while ( ob_get_level() > $ob_level ) {
					ob_end_clean();
				}

				throw $e;
			}

			if ( $this->rest_api_response ) {
				// Use the response from the REST API server.
				ob_end_clean();

				$response_content = $this->rest_api_response['body'];
				$response_headers = array_merge( (array) $response_headers, $this->rest_api_response['headers'] );
				$response_status  = $this->rest_api_response['status'];
			} elseif ( ! $redirected ) {
				try {
					// Execute the request, inasmuch as WordPress would.
					require ABSPATH . WPINC . '/template-loader.php';
				} catch ( Response_Exception $e ) {
					$response_status = $e->status;

					if ( $e instanceof WP_Redirect_Exception ) {
						$response_headers['Location'] = $e->location;
					}

					if ( ! empty( $e->headers ) ) {
						$response_headers = array_merge( $response_headers, $e->headers );
					}
				} catch ( Exception ) { // phpcs:ignore
					// Mantle Exceptions are thrown to prevent some code from running, e.g.
					// the tail end of wp_redirect().
				}

				$response_content = ob_get_clean();
			}

			remove_filter( 'status_header', $intercept_status, 9999 );
			remove_filter( 'wp_headers', $intercept_headers, 9999 );
			remove_filter( 'wp_redirect', $intercept_redirect, 9999 );

			$response = new Test_Response(
				$response_content,
				$response_status ?? 200,
				$response_headers,
				$this->test_case,
			);
		}

		if ( ! $this->test_case->app ) {
			throw new RuntimeException( 'The application instance is not available on the test case.' );
		}

		$response
			->set_app( $this->test_case->app )
			->set_request( $request );

		$this->test_case->call_after_callbacks( $response );

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
	protected function reset_request_state(): void {
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

		$this->rest_api_response = null;

		// Remove all HTTP_* headers from $_SERVER.
		foreach ( array_keys( $_SERVER ) as $key ) {
			if ( str_starts_with( (string) $key, 'HTTP_' ) && 'HTTP_HOST' !== $key ) {
				unset( $_SERVER[ $key ] );
			}
		}

		foreach ( [ 'CONTENT_TYPE', 'QUERY_STRING', 'REMOTE_ADDR', 'REQUEST_SCHEME', 'HTTPS' ] as $header ) {
			if ( isset( $_SERVER[ $header ] ) ) {
				unset( $_SERVER[ $header ] );
			}
		}

		// Clear the "done" global scripts and styles so that scripts/styles are re-output.
		if ( function_exists( 'wp_scripts' ) ) {
			wp_scripts()->done = [];
		}

		if ( function_exists( 'wp_styles' ) ) {
			wp_styles()->done  = [];
		}

		// Reset the assorted hooks back to zero (never run).
		if ( isset( $GLOBALS['wp_actions'] ) && is_array( $GLOBALS['wp_actions'] ) ) {
			foreach ( [ 'wp_print_scripts', 'wp_print_styles', 'the_post' ] as $hook ) {
				$GLOBALS['wp_actions'][ $hook ] = 0;
			}
		}

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
	protected function set_server_state( string $method, string $url, array $server, array $data, array $cookies = [] ): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$_SERVER['REQUEST_METHOD'] = strtoupper( $method );
		$_SERVER['SERVER_PORT']    = '80';

		$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = $this->is_experimental_use_home_url_host_enabled()
			? wp_parse_url( home_url(), PHP_URL_HOST )
			: WP_TESTS_DOMAIN;

		unset( $_SERVER['PATH_INFO'] );

		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) ) {
			$req = $parts['path'] ?? '';
			if ( isset( $parts['query'] ) ) {
				$req .= '?' . $parts['query'];
				// Parse the URL query vars into $_GET.
				parse_str( (string) $parts['query'], $_GET );
			}
		} else {
			$req = $url;
		}

		// Set HTTPS if it is being forced or if the URL being requested is HTTPS.
		if ( $this->forced_https || ( isset( $parts['scheme'] ) && 'https' === $parts['scheme'] ) ) {
			$_SERVER['HTTPS']          = 'on';
			$_SERVER['REQUEST_SCHEME'] = 'https';
			$_SERVER['SERVER_PORT']    = 443;
		} else {
			$_SERVER['REQUEST_SCHEME'] = 'http';
			$_SERVER['SERVER_PORT']    = 80;
		}

		$_SERVER['QUERY_STRING'] = $parts['query'] ?? '';
		$_SERVER['REQUEST_URI']  = $req;

		if ( ! isset( $_SERVER['REMOTE_ADDR'] ) ) { // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders
			$_SERVER['REMOTE_ADDR'] = '127.0.0.1'; // phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		}

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
	protected function setup_wordpress_query(): void {
		/**
		 * Flush the object cache if the test case does not have the
		 * PreserveObjectCache attribute. Since Mantle 0.1, the default has been to
		 * flush the object cache with each testing HTTP request. With Mantle 2.0,
		 * this will flip. Requests will no longer flush the object cache by
		 * default unless it has a FlushObjectCache attribute instead.
		 */
		if ( ! $this->test_case->method_has_attribute( PreserveObjectCache::class ) ) {
			TestCase::flush_cache();
		}

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
	protected function replace_rest_api(): void {
		// Ensure the Mantle REST Spy Server is used.
		add_filter( 'wp_rest_server_class', [ Utils::class, 'wp_rest_server_class_filter' ], PHP_INT_MAX );

		rest_api_init();

		// Replace the `rest_api_loaded()` method with one we can control.
		remove_filter( 'parse_request', 'rest_api_loaded' );
		add_action( 'parse_request', [ $this, 'serve_rest_api_request' ] );
	}

	/**
	 * Get the default URL scheme.
	 *
	 * If the request is being overridden to use HTTPS via {@see with_https()},
	 * this will return 'https'. Otherwise, it will return the scheme of the home
	 * URL of the WordPress installation.
	 *
	 * @return 'http'|'https'
	 */
	protected function get_default_url_scheme(): string {
		if ( $this->forced_https ) {
			return 'https';
		}

		if ( ! $this->is_experimental_use_home_url_host_enabled() ) {
			return 'http';
		}

		$scheme = wp_parse_url( home_url(), PHP_URL_SCHEME );

		if ( empty( $scheme ) ) {
			$scheme = 'http';
		}

		return in_array( $scheme, [ 'http', 'https' ], true ) ? $scheme : 'http';
	}

	/**
	 * Get the default URL host.
	 *
	 * If the `MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST` environment variable
	 * is set, this will return the host of the home URL. Otherwise, it will
	 * return the host defined in the WordPress tests configuration.
	 *
	 * With the next major release of Mantle, we will be shifting to using the
	 * home URL host by default.
	 */
	protected function get_default_url_host(): string {
		return $this->is_experimental_use_home_url_host_enabled()
			? (string) wp_parse_url( home_url(), PHP_URL_HOST )
			: WP_TESTS_DOMAIN;
	}

	/**
	 * Check if the experimental testing URL host feature is enabled.
	 */
	protected function is_experimental_use_home_url_host_enabled(): bool {
		return Utils::env_bool( 'MANTLE_EXPERIMENTAL_TESTING_USE_HOME_URL_HOST', false );
	}

	/**
	 * Server the REST API request if applicable.
	 *
	 * Mirroring `{@see rest_api_loaded()}`, this method fires the REST API
	 * request and stores the response.
	 */
	public function serve_rest_api_request(): void {
		if ( empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
			return;
		}

		$server = rest_get_server();

		if ( $server instanceof Spy_REST_Server ) {
			// Reset the spy to ensure we're not using any previous data.
			$server->reset_spy();

			$route = untrailingslashit( $GLOBALS['wp']->query_vars['rest_route'] );

			if ( empty( $route ) ) {
				$route = '/';
			}

			$server->serve_request( $route );

			if ( $server->sent_body !== null ) {
				$this->rest_api_response = [
					'body'    => $server->sent_body ?? '',
					'headers' => $server->sent_headers ?? [],
					'status'  => $server->sent_status ?? 200,
				];
			}
		} else {
			trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
				'Expected the Mantle Spy REST Server to be used. Not able to be tested against.',
				E_USER_WARNING,
			);
		}
	}

	/**
	 * Turn the given URI into a fully qualified URL.
	 *
	 * @param string $uri URI to fully-qualify.
	 */
	protected function prepare_url_for_request( $uri ): string {
		return Str::trailing_slash( home_url( $uri ) );
	}

	/**
	 * Follow a redirect chain until a non-redirect is received.
	 *
	 * @param Test_Response $response Test response.
	 */
	protected function follow_redirects( $response ): Test_Response {
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
	 * @param int    $options JSON encoding options.
	 */
	public function get_json( string $uri, array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'GET', $uri, [], $headers, $options );
	}

	/**
	 * Call the given URI with a JSON request.
	 *
	 * @param string $method  Request method.
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 *
	 * @throws RuntimeException If not implemented.
	 */
	public function json( string $method, string $uri, array $data = [], array $headers = [], int $options = 1 ): Test_Response {
		$content = (string) json_encode( $data, $options ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode

		$headers = array_merge(
			$headers,
			[
				'Accept'         => 'application/json',
				'Content-Length' => mb_strlen( $content, '8bit' ),
				'Content-Type'   => 'application/json',
			]
		);

		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( $method, $uri, $data, $server, [], $content );
	}

	/**
	 * Visit the given URI with a POST request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function post( string $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'POST', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a POST request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function post_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'POST', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a PUT request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function put( string $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PUT', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a PUT request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function put_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'PUT', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a PATCH request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function patch( $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'PATCH', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a PATCH request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function patch_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'PATCH', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a DELETE request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function delete( $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'DELETE', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a DELETE request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function delete_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'DELETE', $uri, $data, $headers, $options );
	}

	/**
	 * Visit the given URI with a OPTIONS request.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 */
	public function options( $uri, array $data = [], array $headers = [] ): Test_Response {
		$server = $this->transform_headers_to_server_vars( $headers );

		return $this->call( 'OPTIONS', $uri, $data, $server );
	}

	/**
	 * Visit the given URI with a OPTIONS request, expecting a JSON response.
	 *
	 * @param string $uri     Request URI.
	 * @param array  $data    Request data.
	 * @param array  $headers Request headers.
	 * @param int    $options JSON encoding options.
	 */
	public function options_json( string $uri, array $data = [], array $headers = [], int $options = 0 ): Test_Response {
		return $this->json( 'OPTIONS', $uri, $data, $headers, $options );
	}
}
