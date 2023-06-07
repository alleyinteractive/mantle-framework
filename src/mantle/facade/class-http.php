<?php
/**
 * Http Facade class file
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\Http_Client\Factory;

/**
 * Http Facade
 *
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool has_macro(string $name)
 * @method static mixed macro_call(string $method, array $parameters)
 * @method static \Mantle\Http_Client\Pending_Request create()
 * @method static \Mantle\Http_Client\Pending_Request as_form()
 * @method static \Mantle\Http_Client\Pending_Request as_json()
 * @method static \Mantle\Http_Client\Pending_Request base_url(string $url)
 * @method static \Mantle\Http_Client\Pending_Request|string url(string $url = null)
 * @method static \Mantle\Http_Client\Pending_Request|string method(string $method = null)
 * @method static \Mantle\Http_Client\Pending_Request with_body(string $content, string $content_type)
 * @method static mixed body()
 * @method static \Mantle\Http_Client\Pending_Request with_options(array $options, bool $merge = true)
 * @method static \Mantle\Http_Client\Pending_Request body_format(string $format)
 * @method static \Mantle\Http_Client\Pending_Request content_type(string $content_type)
 * @method static \Mantle\Http_Client\Pending_Request accept_json()
 * @method static \Mantle\Http_Client\Pending_Request accept(string $content_type)
 * @method static \Mantle\Http_Client\Pending_Request with_headers(array $headers)
 * @method static \Mantle\Http_Client\Pending_Request with_header(string $key, mixed $value, bool $replace = false)
 * @method static array headers()
 * @method static \Mantle\Http_Client\Pending_Request clear_headers()
 * @method static mixed header(string $key)
 * @method static \Mantle\Http_Client\Pending_Request with_basic_auth(string $username, string $password)
 * @method static \Mantle\Http_Client\Pending_Request with_token(string $token, string $type = 'Bearer')
 * @method static \Mantle\Http_Client\Pending_Request with_user_agent(string $user_agent)
 * @method static \Mantle\Http_Client\Pending_Request clear_cookies()
 * @method static \Mantle\Http_Client\Pending_Request with_cookies(\WP_Http_Cookie[] $cookies)
 * @method static \Mantle\Http_Client\Pending_Request with_cookie(\WP_Http_Cookie $cookie)
 * @method static \Mantle\Http_Client\Pending_Request without_redirecting()
 * @method static \Mantle\Http_Client\Pending_Request with_redirecting(int $times = 5)
 * @method static \Mantle\Http_Client\Pending_Request without_verifying()
 * @method static \Mantle\Http_Client\Pending_Request timeout(int $seconds)
 * @method static \Mantle\Http_Client\Pending_Request middleware(callable $middleware)
 * @method static \Mantle\Http_Client\Pending_Request without_middleware()
 * @method static \static stream(string|null $file = null)
 * @method static \Mantle\Http_Client\Pending_Request dont_stream()
 * @method static \Mantle\Http_Client\Pending_Request retry(int $retry, int $delay = 0)
 * @method static \Mantle\Http_Client\Pending_Request throw_exception()
 * @method static \Mantle\Http_Client\Pending_Request dont_throw_exception()
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request get(string $url, array|string|null $query = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request head(string $url, array|string|null $query = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request post(string $url, array $data = [])
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request patch(string $url, array $data = [])
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request put(string $url, array $data = [])
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request delete(string $url, array $data = [])
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request send(string $method, string $url, array $options = [])
 * @method static \Mantle\Http_Client\Pending_Request pooled(bool $pooled = true)
 * @method static array pool(callable $callback)
 * @method static array get_request_args()
 * @method static \Mantle\Http_Client\Pending_Request|mixed when(\Closure|mixed $value, callable|null $callback = null, callable|null $default = null)
 * @method static \Mantle\Http_Client\Pending_Request|mixed unless(\Closure|mixed $value, callable|null $callback = null, callable|null $default = null)
 *
 * @see \Mantle\Http_Client\Factory
 */
class Http extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return Factory::class;
	}
}
