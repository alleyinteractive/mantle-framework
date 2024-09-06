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
 * @method static \Mantle\Http_Client\Pending_Request create()
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool has_macro(string $name)
 * @method static mixed macro_call(string $method, array $parameters)
 * @method static \static as_form()
 * @method static \static as_json()
 * @method static \static base_url(string $url)
 * @method static \Mantle\Http_Client\Pending_Request|string url(string $url = null)
 * @method static \Mantle\Http_Client\Pending_Request|string method(string $method = null)
 * @method static \static with_body(string $content, string $content_type)
 * @method static \static with_json(array $data)
 * @method static mixed|null body()
 * @method static \static with_options(array $options, bool $merge = true)
 * @method static \Mantle\Http_Client\Pending_Request body_format(string $format)
 * @method static \static content_type(string $content_type)
 * @method static \static accept_json()
 * @method static \static accept(string $content_type)
 * @method static \static with_headers(array $headers)
 * @method static \static with_header(string $key, mixed $value, bool $replace = false)
 * @method static array headers()
 * @method static \static clear_headers()
 * @method static mixed|null header(string $key)
 * @method static \static with_basic_auth(string $username, string $password)
 * @method static \static with_token(string $token, string $type = 'Bearer')
 * @method static \static with_user_agent(string $user_agent)
 * @method static \static clear_cookies()
 * @method static \static with_cookies(\WP_Http_Cookie[] $cookies)
 * @method static \static with_cookie(\WP_Http_Cookie $cookie)
 * @method static \static without_redirecting()
 * @method static \static with_redirecting(int $times = 5)
 * @method static \static without_verifying()
 * @method static \static timeout(int $seconds)
 * @method static \static middleware(callable $middleware)
 * @method static \static without_middleware()
 * @method static \static stream(string|null $file = null)
 * @method static \static dont_stream()
 * @method static \static retry(int $retry, int $delay = 0)
 * @method static \static throw_exception()
 * @method static \static dont_throw_exception()
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request get(string $url, array|string|null $query = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request head(string $url, array|string|null $query = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request post(string $url, array $data = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request patch(string $url, array $data = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request put(string $url, array $data = null)
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request delete(string $url, array $data = [])
 * @method static \Mantle\Http_Client\Response|\Mantle\Http_Client\Pending_Request send(string $method, string $url, array $options = [])
 * @method static \static pooled(bool $pooled = true)
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
	 */
	protected static function get_facade_accessor(): string {
		return Factory::class;
	}
}
