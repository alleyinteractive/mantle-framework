<?php
/**
 * Redirector class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Http\Routing;

use Symfony\Component\HttpFoundation\RedirectResponse;

use function Mantle\Framework\Helpers\tap;

/**
 * Redirector
 */
class Redirector {
	public const STATUS_PERMANENT = 301;
	public const STATUS_TEMPORARY = 302;

	/**
	 * URL Generator instance.
	 *
	 * @var Url_Generator
	 */
	protected $generator;

	public function __construct( Url_Generator $generator ) {
		$this->generator = $generator;
	}

	public function home( int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		var_dump($this->generator->to( '/', [], null ));
		return $this->to( $this->generator->to( '/', [], null ), $status, $headers );
	}

	public function back( int $status = self::STATUS_TEMPORARY, array $headers = [], string $fallback = null ): RedirectResponse {
		return $this->to( $this->generator->previous( $fallback ), $status, $headers );
	}

	public function refresh( int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $this->generator->get_request()->path(), $status, $headers );
	}

	public function to( string $path, int $status = self::STATUS_TEMPORARY, array $headers = [], bool $secure = null ): RedirectResponse {
		return $this->create_redirect(
			$this->generator->to( $path, [], $secure ),
			$status,
			$headers
		);
	}

	public function away( string $path, int $status = self::STATUS_TEMPORARY, array $headers = [] ) {
		return $this->create_redirect(
			$path,
			$status,
			$headers
		);
	}

	public function secure( string $path, int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $path, $status, $headers, true );
	}

	public function route( string $route, array $parameters = [], int $status = self::STATUS_TEMPORARY, array $headers = [] ): RedirectResponse {
		return $this->to( $this->generator->generate( $route, $parameters ), $status, $headers );
	}

	/**
	 * Create a redirect response.
	 *
	 * @param string $path URL path.
	 * @param int    $status HTTP status code.
	 * @param array  $headers Array of headers, optional.
	 * @return RedirectResponse
	 */
	protected function create_redirect( string $path, int $status, array $headers = [] ): RedirectResponse {
		return new RedirectResponse( $path, $status, $headers );
	}
}
