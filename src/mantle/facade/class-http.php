<?php
/**
 * Http Facade class file
 *
 * @package Mantle
 */

namespace Mantle\Facade;

use Mantle\Http\Client\Http_Client;

/**
 * Http Facade
 *
 * @method static \Mantle\Http\Client\Http_Client as_form()
 * @method static \Mantle\Http\Client\Http_Client as_json()
 * @method static \Mantle\Http\Client\Http_Client attach( string|array $name, string|resource $contents = '', ?string $filename = null, array $headers = [] )
 * @method static \Mantle\Http\Client\Http_Client base_url( string $url )
 * @method static \Mantle\Http\Client\Http_Client with_body( string $content, string $content_type )
 * @method static \Mantle\Http\Client\Http_Client with_options( array $options )
 * @method static \Mantle\Http\Client\Http_Client body_format( string $format)
 * @method static \Mantle\Http\Client\Http_Client content_type( string $content_type )
 * @method static \Mantle\Http\Client\Http_Client accept_json()
 * @method static \Mantle\Http\Client\Http_Client accept( string $content_type )
 * @method static \Mantle\Http\Client\Http_Client with_headers( array $headers)
 * @method static \Mantle\Http\Client\Http_Client with_header( string $key, $value )
 * @method static \Mantle\Http\Client\Http_Client with_basic_auth( string $username, string $password)
 * @method static \Mantle\Http\Client\Http_Client with_digest_auth( string $username, string $password )
 * @method static \Mantle\Http\Client\Http_Client with_token( string $token, string $type = 'Bearer' )
 * @method static \Mantle\Http\Client\Http_Client with_user_agent( string $user_agent )
 * @method static \Mantle\Http\Client\Http_Client with_cookies( \WP_Http_Cookie[] $cookies )
 * @method static \Mantle\Http\Client\Http_Client with_cookie( \WP_Http_Cookie $cookie )
 * @method static \Mantle\Http\Client\Http_Client clear_cookies()
 * @method static \Mantle\Http\Client\Http_Client without_redirecting()
 * @method static \Mantle\Http\Client\Http_Client with_redirecting( int $times = 5 )
 * @method static \Mantle\Http\Client\Http_Client without_verifying()
 * @method static \Mantle\Http\Client\Http_Client timeout( int $seconds )
 * @method static \Mantle\Http\Client\Http_Client retry( int $retry )
 * @method static \Mantle\Http\Client\Http_Client get( string $url, $query = null )
 * @method static \Mantle\Http\Client\Http_Client head( string $url, $query = null )
 * @method static \Mantle\Http\Client\Http_Client post( string $url, array $data = [] )
 * @method static \Mantle\Http\Client\Http_Client patch( string $url, $data = [] )
 * @method static \Mantle\Http\Client\Http_Client put( string $url, array $data = [] )
 * @method static \Mantle\Http\Client\Http_Client delete( string $url, array $data = [] )
 */
class Http extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function get_facade_accessor(): string {
		return Http_Client::class;
	}
}
