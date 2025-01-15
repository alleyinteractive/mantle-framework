<?php
/**
 * Request Facade class file.
 *
 * @package Mantle
 */

namespace Mantle\Facade;

/**
 * Request Facade
 *
 * @method static \Mantle\Http\Request capture()
 * @method static \Mantle\Http\Request setPathInfo(string $path_info)
 * @method static \Mantle\Http\Request instance()
 * @method static string method()
 * @method static string root()
 * @method static string url()
 * @method static string full_url()
 * @method static string full_url_with_query(array $query)
 * @method static string path()
 * @method static string decoded_path()
 * @method static string|null segment(int $index, string|null $default = null)
 * @method static array segments()
 * @method static bool is(mixed ...$patterns)
 * @method static bool full_url_is(mixed ...$patterns)
 * @method static bool ajax()
 * @method static bool pjax()
 * @method static bool prefetch()
 * @method static bool secure()
 * @method static string|null ip()
 * @method static array ips()
 * @method static string|null user_agent()
 * @method static \Mantle\Http\Request merge(array $input)
 * @method static \Mantle\Http\Request replace(array $input)
 * @method static mixed|null get(string $key, mixed $default = null)
 * @method static \Symfony\Component\HttpFoundation\ParameterBag|mixed json(string|null $key = null, mixed $default = null)
 * @method static bool is_json()
 * @method static \Mantle\Http\Request set_json(\Symfony\Component\HttpFoundation\ParameterBag $json)
 * @method static array to_array()
 * @method static \Mantle\Http\Request set_route_parameters(\Symfony\Component\HttpFoundation\ParameterBag|array $parameters)
 * @method static \Symfony\Component\HttpFoundation\ParameterBag|null get_route_parameters()
 * @method static \Mantle\Http\Request set_route_parameter(string $key, mixed $value)
 * @method static \Mantle\Http\Routing\Route get_route()
 * @method static \Mantle\Http\Request set_route(\Mantle\Http\Routing\Route $route)
 * @method static void initialize(array $query = [], array $request = [], array $attributes = [], array $cookies = [], array $files = [], array $server = [], string|resource|null $content = null)
 * @method static \Mantle\Http\Request createFromGlobals()
 * @method static \Mantle\Http\Request create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], string|resource|null $content = null)
 * @method static void setFactory(callable|null $callable)
 * @method static \Mantle\Http\Request duplicate(array|null $query = null, array|null $request = null, array|null $attributes = null, array|null $cookies = null, array|null $files = null, array|null $server = null)
 * @method static void overrideGlobals()
 * @method static void setTrustedProxies(array $proxies, int $trustedHeaderSet)
 * @method static string[] getTrustedProxies()
 * @method static int getTrustedHeaderSet()
 * @method static void setTrustedHosts(array $hostPatterns)
 * @method static string[] getTrustedHosts()
 * @method static string normalizeQueryString(string|null $qs)
 * @method static void enableHttpMethodParameterOverride()
 * @method static bool getHttpMethodParameterOverride()
 * @method static \Symfony\Component\HttpFoundation\Session\SessionInterface getSession()
 * @method static bool hasPreviousSession()
 * @method static bool hasSession(bool $skipIfUninitialized = false)
 * @method static void setSession(\Symfony\Component\HttpFoundation\Session\SessionInterface $session)
 * @method static array getClientIps()
 * @method static string|null getClientIp()
 * @method static string getScriptName()
 * @method static string getPathInfo()
 * @method static string getBasePath()
 * @method static string getBaseUrl()
 * @method static string getScheme()
 * @method static int|string|null getPort()
 * @method static string|null getUser()
 * @method static string|null getPassword()
 * @method static string|null getUserInfo()
 * @method static string getHttpHost()
 * @method static string getRequestUri()
 * @method static string getSchemeAndHttpHost()
 * @method static string getUri()
 * @method static string getUriForPath(string $path)
 * @method static string getRelativeUriForPath(string $path)
 * @method static string|null getQueryString()
 * @method static bool isSecure()
 * @method static string getHost()
 * @method static void setMethod(string $method)
 * @method static string getMethod()
 * @method static string getRealMethod()
 * @method static string|null getMimeType(string $format)
 * @method static string[] getMimeTypes(string $format)
 * @method static string|null getFormat(string|null $mimeType)
 * @method static void setFormat(string|null $format, string|string[] $mimeTypes)
 * @method static string|null getRequestFormat(string|null $default = 'html')
 * @method static void setRequestFormat(string|null $format)
 * @method static string|null getContentTypeFormat()
 * @method static void setDefaultLocale(string $locale)
 * @method static string getDefaultLocale()
 * @method static void setLocale(string $locale)
 * @method static string getLocale()
 * @method static bool isMethod(string $method)
 * @method static bool isMethodSafe()
 * @method static bool isMethodIdempotent()
 * @method static bool isMethodCacheable()
 * @method static string|null getProtocolVersion()
 * @method static string|resource getContent(bool $asResource = false)
 * @method static \Symfony\Component\HttpFoundation\InputBag getPayload()
 * @method static array toArray()
 * @method static array getETags()
 * @method static bool isNoCache()
 * @method static string|null getPreferredFormat(string|null $default = 'html')
 * @method static string|null getPreferredLanguage(string[] $locales = null)
 * @method static string[] getLanguages()
 * @method static string[] getCharsets()
 * @method static string[] getEncodings()
 * @method static string[] getAcceptableContentTypes()
 * @method static bool isXmlHttpRequest()
 * @method static bool preferSafeContent()
 * @method static bool isFromTrustedProxy()
 * @method static string|array|null server(string|null $key = null, string|array|null $default = null)
 * @method static bool has_header(string $key)
 * @method static array|string|null header(string|null $key = null, string|array|null $default = null)
 * @method static string|null bearer_token()
 * @method static bool exists(string|array $key)
 * @method static bool has(string|array $key)
 * @method static bool has_any(string|array $keys)
 * @method static bool filled(string|array $key)
 * @method static bool any_filled(string|array $keys)
 * @method static bool missing(string|array $key)
 * @method static array keys()
 * @method static array all(array|mixed|null $keys = null)
 * @method static mixed input(string|null $key = null, mixed $default = null)
 * @method static bool boolean(string|null $key = null, bool $default = false)
 * @method static array only(array|mixed $keys)
 * @method static array except(array|mixed $keys)
 * @method static string|array|null query(string|null $key = null, string|array|null $default = null)
 * @method static string|array|null post(string|null $key = null, string|array|null $default = null)
 * @method static bool has_cookie(string $key)
 * @method static string|array|null cookie(string|null $key = null, string|array|null $default = null)
 * @method static array all_files()
 * @method static bool has_file(string $key)
 * @method static \Mantle\Http\Uploaded_File|\Mantle\Http\Uploaded_File[]|null file(string|null $key = null, mixed $default = null)
 * @method static bool matches_type(string $actual, string $type)
 * @method static bool expects_json()
 * @method static bool wants_json()
 * @method static bool accepts(string|array $content_types)
 * @method static string|null prefers(string|array $content_types)
 * @method static bool accepts_any_content_type()
 * @method static bool accepts_json()
 * @method static bool accepts_html()
 * @method static string format(string $default = 'html')
 *
 * @see \Mantle\Http\Request
 */
class Request extends Facade {
	/**
	 * Get the registered name of the component.
	 */
	protected static function get_facade_accessor(): string {
		return 'request';
	}
}
