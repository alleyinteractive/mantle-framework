<?php

namespace Mantle\Support;

use Closure;
use Mantle\Contracts\Routing\UrlRoutable;
use Mantle\Contracts\Support\Htmlable;
use Mantle\Support\Traits\Conditionable;
use Mantle\Support\Traits\Macroable;
use Mantle\Support\Traits\Tappable;
use League\Uri\Contracts\UriInterface;
use League\Uri\Uri as LeagueUri;
use Symfony\Component\HttpFoundation\RedirectResponse;
use SensitiveParameter;
use Stringable;

use function Mantle\Support\Helpers\data_set;

class Uri implements Htmlable, Stringable {
	use Conditionable;
	use Macroable;
	use Tappable;

	/**
	 * The URI instance.
	 */
	protected UriInterface $uri;

	/**
	 * The URL generator resolver.
	 */
	// protected static ?Closure $url_generator_resolver = null;

	public static function current(): static {
		return new static( LeagueUri::fromServer( $_SERVER ) );
	}

	/**
	 * Create a new parsed URI instance.
	 */
	public function __construct( UriInterface|Stringable|string $uri = '' ) {
		$this->uri = $uri instanceof UriInterface ? $uri : LeagueUri::new( (string) $uri );
	}

	/**
	 * Create a new URI instance.
	 */
	public static function of( UriInterface|Stringable|string $uri = '' ): static {
		return new static( $uri );
	}

	/**
	 * Get a URI instance of an absolute URL for the given path.
	 */
	// public static function to( string $path ): static {
	// 	return new static( call_user_func( static::$url_generator_resolver )->to( $path ) );
	// }

	// /**
	//  * Get a URI instance for a named route.
	//  *
	//  * @param  \BackedEnum|string $name
	//  * @param  mixed              $parameters
	//  * @param  bool               $absolute
	//  *
	//  * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException|\InvalidArgumentException
	//  */
	// public static function route( $name, $parameters = [], $absolute = true ): static {
	// 	return new static( call_user_func( static::$url_generator_resolver )->route( $name, $parameters, $absolute ) );
	// }

	// /**
	//  * Create a signed route URI instance for a named route.
	//  *
	//  * @param  \BackedEnum|string                        $name
	//  * @param  mixed                                     $parameters
	//  * @param  \DateTimeInterface|\DateInterval|int|null $expiration
	//  * @param  bool                                      $absolute
	//  *
	//  * @throws \InvalidArgumentException
	//  */
	// public static function signed_route( $name, $parameters = [], $expiration = null, $absolute = true ): static {
	// 	return new static( call_user_func( static::$url_generator_resolver )->signedRoute( $name, $parameters, $expiration, $absolute ) );
	// }

	// /**
	//  * Create a temporary signed route URI instance for a named route.
	//  *
	//  * @param  \BackedEnum|string                   $name
	//  * @param  \DateTimeInterface|\DateInterval|int $expiration
	//  * @param  array                                $parameters
	//  * @param  bool                                 $absolute
	//  */
	// public static function temporary_signed_route( $name, $expiration, $parameters = [], $absolute = true ): static {
	// 	return static::signedRoute( $name, $parameters, $expiration, $absolute );
	// }

	// /**
	//  * Get a URI instance for a controller action.
	//  *
	//  * @param  string|array $action
	//  * @param  mixed        $parameters
	//  * @param  bool         $absolute
	//  *
	//  * @throws \InvalidArgumentException
	//  */
	// public static function action( $action, $parameters = [], $absolute = true ): static {
	// 	return new static( call_user_func( static::$url_generator_resolver )->action( $action, $parameters, $absolute ) );
	// }

	/**
	 * Get the URI's scheme.
	 */
	public function scheme(): ?string {
		return $this->uri->getScheme();
	}

	/**
	 * Get the user from the URI.
	 */
	public function user( bool $withPassword = false ): ?string {
		return $withPassword
			? $this->uri->getUserInfo()
			: $this->uri->getUsername();
	}

	/**
	 * Get the password from the URI.
	 */
	public function password(): ?string {
		return $this->uri->getPassword();
	}

	/**
	 * Get the URI's host.
	 */
	public function host(): ?string {
		return $this->uri->getHost();
	}

	/**
	 * Get the URI's port.
	 */
	public function port(): ?int {
		return $this->uri->getPort();
	}

	/**
	 * Get the URI's path.
	 *
	 * Empty or missing paths are returned as a single "/".
	 */
	public function path(): ?string {
		$path = trim( $this->uri->getPath(), '/' );

		return $path === '' ? '/' : $path;
	}

	/**
	 * Get the URI's path segments.
	 *
	 * Empty or missing paths are returned as an empty collection.
	 */
	public function path_segments(): Collection {
		$path = $this->path();

		return $path === '/' ? new Collection() : new Collection( explode( '/', (string) $path ) );
	}

	/**
	 * Get the URI's query string.
	 */
	public function query(): Uri_Query_String {
		return Uri_Query_String::create( $this );
	}

	/**
	 * Get the URI's fragment.
	 */
	public function fragment(): ?string {
		return $this->uri->getFragment();
	}

	/**
	 * Specify the scheme of the URI.
	 */
	public function with_scheme( Stringable|string $scheme ): static {
		return new static( $this->uri->withScheme( $scheme ) );
	}

	/**
	 * Specify the user and password for the URI.
	 */
	public function with_user( Stringable|string|null $user, #[SensitiveParameter] Stringable|string|null $password = null ): static {
		return new static( $this->uri->withUserInfo( $user, $password ) );
	}

	/**
	 * Specify the host of the URI.
	 */
	public function with_host( Stringable|string $host ): static {
		return new static( $this->uri->withHost( $host ) );
	}

	/**
	 * Specify the port of the URI.
	 */
	public function with_port( ?int $port ): static {
		return new static( $this->uri->withPort( $port ) );
	}

	/**
	 * Specify the path of the URI.
	 */
	public function with_path( Stringable|string $path ): static {
		return new static( $this->uri->withPath( Str::start( (string) $path, '/' ) ) );
	}

	/**
	 * Merge new query parameters into the URI.
	 */
	public function with_query( array $query, bool $merge = true ): static {
		if ( $merge ) {
			$mergedQuery = $this->query()->all();

			foreach ( $query as $key => $value ) {
				data_set( $mergedQuery, $key, $value );
			}

			$newQuery = $mergedQuery;
		} else {
			$newQuery = [];

			foreach ( $query as $key => $value ) {
				data_set( $newQuery, $key, $value );
			}
		}

		return new static( $this->uri->withQuery( Arr::query( $newQuery ) ?: null ) );
	}

	/**
	 * Merge new query parameters into the URI if they are not already in the query string.
	 */
	public function with_query_if_missing( array $query ): static {
		$current = $this->query();

		foreach ( array_keys( $query ) as $key ) {
			if ( ! $current->missing( $key ) ) {
				Arr::forget( $query, $key );
			}
		}

		return $this->with_query( $query );
	}

	/**
	 * Push a value onto the end of a query string parameter that is a list.
	 */
	public function push_onto_query( string $key, mixed $value ): static {
		$current = data_get( $this->query()->all(), $key );

		$values = Arr::wrap( $value );

		return $this->with_query( [
			$key => match ( true ) {
				is_array( $current ) && array_is_list( $current ) => array_values( array_unique( [ ...$current, ...$values ] ) ),
				is_array( $current ) => [ ...$current, ...$values ],
				! is_null( $current ) => [ $current, ...$values ],
				default => $values,
			},
		] );
	}

	/**
	 * Remove the given query parameters from the URI.
	 */
	public function without_query( array|string $keys ): static {
		return $this->replace_query( Arr::except( $this->query()->all(), $keys ) );
	}

	/**
	 * Remove all query parameters from the URI.
	 */
	public function remove_query(): static {
		return $this->replace_query( [] );
	}

	/**
	 * Specify new query parameters for the URI.
	 */
	public function replace_query( array $query ): static {
		return $this->with_query( $query, merge: false );
	}

	/**
	 * Specify the fragment of the URI.
	 */
	public function with_fragment( string $fragment ): static {
		return new static( $this->uri->withFragment( $fragment ) );
	}

	/**
	 * Create a redirect HTTP response for the given URI.
	 */
	public function redirect( int $status = 302, array $headers = [] ): RedirectResponse {
		return new RedirectResponse( $this->value(), $status, $headers );
	}

	/**
	 * Create an HTTP response that represents the object.
	 *
	 * @param  \Illuminate\Http\Request $request
	 */
	public function to_response( $request ): RedirectResponse {
		return new RedirectResponse( $this->value() );
	}

	/**
	 * Get content as a string of HTML.
	 */
	public function to_html(): string {
		return $this->value();
	}

	/**
	 * Get the decoded string representation of the URI.
	 */
	public function decode(): string {
		$this->getUri()->getQuery()->__toString();
		if ( empty( $this->query()->to_array() ) ) {
			return $this->value();
		}

		return Str::replace( Str::after( $this->value(), '?' ), $this->query()->decode(), $this->value() );
	}

	/**
	 * Get the string representation of the URI.
	 */
	public function value(): string {
		return (string) $this;
	}

	/**
	 * Determine if the URI is currently an empty string.
	 */
	public function is_empty(): bool {
		return trim( $this->value() ) === '';
	}

	/**
	 * Dump the string representation of the URI.
	 *
	 * @param  mixed ...$args
	 * @return $this
	 */
	public function dump( ...$args ): static {
		dump( $this->value(), ...$args );

		return $this;
	}

	/**
	 * Set the URL generator resolver.
	 */
	public static function set_url_generator_resolver( Closure $url_generator_resolver ): void {
		static::$url_generator_resolver = $url_generator_resolver;
	}

	/**
	 * Get the underlying URI instance.
	 */
	public function getUri(): UriInterface {
		return $this->uri;
	}

	/**
	 * Get the string representation of the URI.
	 */
	public function __toString(): string {
		return $this->uri->toString();
	}
}
