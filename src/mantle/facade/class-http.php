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
 * @method static \Mantle\Http_Client\Pending_Request as_form()
 * @method static \Mantle\Http_Client\Pending_Request as_json()
 * @method static \Mantle\Http_Client\Pending_Request attach( string|array $name, string|resource $contents = '', ?string $filename = null, array $headers = [] )
 * @method static \Mantle\Http_Client\Pending_Request base_url( string $url )
 * @method static \Mantle\Http_Client\Pending_Request with_body( string $content, string $content_type )
 * @method static \Mantle\Http_Client\Pending_Request with_options( array $options )
 * @method static \Mantle\Http_Client\Pending_Request body_format( string $format)
 * @method static \Mantle\Http_Client\Pending_Request content_type( string $content_type )
 * @method static \Mantle\Http_Client\Pending_Request accept_json()
 * @method static \Mantle\Http_Client\Pending_Request accept( string $content_type )
 * @method static \Mantle\Http_Client\Pending_Request with_headers( array $headers)
 * @method static \Mantle\Http_Client\Pending_Request with_header( string $key, $value )
 * @method static \Mantle\Http_Client\Pending_Request with_basic_auth( string $username, string $password)
 * @method static \Mantle\Http_Client\Pending_Request with_digest_auth( string $username, string $password )
 * @method static \Mantle\Http_Client\Pending_Request with_token( string $token, string $type = 'Bearer' )
 * @method static \Mantle\Http_Client\Pending_Request with_user_agent( string $user_agent )
 * @method static \Mantle\Http_Client\Pending_Request with_cookies( \WP_Http_Cookie[] $cookies )
 * @method static \Mantle\Http_Client\Pending_Request with_cookie( \WP_Http_Cookie $cookie )
 * @method static \Mantle\Http_Client\Pending_Request clear_cookies()
 * @method static \Mantle\Http_Client\Pending_Request without_redirecting()
 * @method static \Mantle\Http_Client\Pending_Request with_redirecting( int $times = 5 )
 * @method static \Mantle\Http_Client\Pending_Request without_verifying()
 * @method static \Mantle\Http_Client\Pending_Request timeout( int $seconds )
 * @method static \Mantle\Http_Client\Pending_Request retry( int $retry, int $delay = 0 )
 * @method static \Mantle\Http_Client\Response|static get( string $url, $query = null )
 * @method static \Mantle\Http_Client\Response|static head( string $url, $query = null )
 * @method static \Mantle\Http_Client\Response|static post( string $url, array $data = [] )
 * @method static \Mantle\Http_Client\Response|static patch( string $url, $data = [] )
 * @method static \Mantle\Http_Client\Response|static put( string $url, array $data = [] )
 * @method static \Mantle\Http_Client\Response|static delete( string $url, array $data = [] )
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
