<?php
/**
 * Http Facade class file
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\Http_Client\Factory;

/**
 * Http
 *
 * @method static void macro(string $name, object|callable $macro)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static bool has_macro(string $name)
 * @method static mixed macro_call(string $method, array $parameters)
 * @method static static \Mantle\Http_Client\Pending_Request accept_json()
 * @method static static \Mantle\Http_Client\Pending_Request accept( string $content_type )
 * @method static static \Mantle\Http_Client\Pending_Request as_form()
 * @method static static \Mantle\Http_Client\Pending_Request as_json()
 * @method static static \Mantle\Http_Client\Pending_Request attach( string|array $name, string|resource $contents = '', ?string $filename = null, array $headers = [] )
 * @method static static \Mantle\Http_Client\Pending_Request base_url( string $url )
 * @method static static \Mantle\Http_Client\Pending_Request body_format( string $format)
 * @method static static \Mantle\Http_Client\Pending_Request content_type( string $content_type )
 * @method static static \Mantle\Http_Client\Pending_Request dont_stream()
 * @method static static \Mantle\Http_Client\Pending_Request stream( ?string $file )
 * @method static static \Mantle\Http_Client\Pending_Request with_body( string $content, string $content_type )
 * @method static static \Mantle\Http_Client\Pending_Request with_options( array $options, bool $merge = true )
 * @method static static \Mantle\Http_Client\Pending_Request with_headers( array $headers)
 * @method static static \Mantle\Http_Client\Pending_Request with_header( string $key, $value )
 * @method static static \Mantle\Http_Client\Pending_Request with_basic_auth( string $username, string $password)
 * @method static static \Mantle\Http_Client\Pending_Request with_digest_auth( string $username, string $password )
 * @method static static \Mantle\Http_Client\Pending_Request with_token( string $token, string $type = 'Bearer' )
 * @method static static \Mantle\Http_Client\Pending_Request with_user_agent( string $user_agent )
 * @method static static \Mantle\Http_Client\Pending_Request with_cookies( \WP_Http_Cookie[] $cookies )
 * @method static static \Mantle\Http_Client\Pending_Request with_cookie( \WP_Http_Cookie $cookie )
 * @method static static \Mantle\Http_Client\Pending_Request clear_cookies()
 * @method static static \Mantle\Http_Client\Pending_Request without_redirecting()
 * @method static static \Mantle\Http_Client\Pending_Request with_redirecting( int $times = 5 )
 * @method static static \Mantle\Http_Client\Pending_Request without_verifying()
 * @method static static \Mantle\Http_Client\Pending_Request timeout( int $seconds )
 * @method static static \Mantle\Http_Client\Pending_Request retry( int $retry, int $delay = 0 )
 * @method static static \Mantle\Http_Client\Response|static get( string $url, $query = null )
 * @method static static \Mantle\Http_Client\Response|static head( string $url, $query = null )
 * @method static static \Mantle\Http_Client\Response|static post( string $url, array $data = [] )
 * @method static static \Mantle\Http_Client\Response|static patch( string $url, $data = [] )
 * @method static static \Mantle\Http_Client\Response|static put( string $url, array $data = [] )
 * @method static static \Mantle\Http_Client\Response|static delete( string $url, array $data = [] )
 * @method static static array pool( callable $callback )
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
