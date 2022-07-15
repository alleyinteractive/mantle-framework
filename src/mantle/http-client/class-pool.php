<?php
namespace Mantle\Http_Client;

/**
 * Http Pool
 *
 * @mixin \Mantle\Http_Client\Http_Client
 */
class Pool {
	/**
	 * Pool of pending requests.
	 *
	 * @var array<string|int, Pending_Request>
	 */
	protected array $pool = [];

	/**
	 * Constructor.
	 *
	 * @param Http_Client|null $client
	 */
	public function __construct( Http_Client $client = null ) {
		$this->client = $client ?: new Http_Client();
	}

	/**
	 * Create a pending request for the pool
	 *
	 * @return Pending_Request
	 */
	protected function create_request(): Pending_Request {
		return new Pending_Request( $this->client );
	}

	/**
	 * Retrieve the requests for the given pool
	 *
	 * @return array<int|string, Pending_Request>
	 */
	public function get_requests(): array {
		return $this->pool;
	}

	/**
	 * Call a pending request a specific name.
	 *
	 * @param string $key The name of the pending request.
	 * @return Pending_Request
	 */
	public function as( string $key ): Pending_Request {
		$this->pool[ $key ] = $this->create_request();

		return $this->pool[ $key ];
	}

	/**
	 * Add a request to the pool with a numeric index
	 *
	 * @param string $method
	 * @param array $args
	 * @return Pending_Request
	 */
	public function __call( string $method, array $args = [] ) {
		$request = $this->create_request()->{$method}( ...$args );

		$this->pool[] = $request;

		return $request;
	}
}
