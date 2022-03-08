<?php
/**
 * This file contains the Mock_Http_Sequence class
 *
 * @package Mantle
 */

namespace Mantle\Testing;

/**
 * Support faking HTTP requests in a specific sequence.
 */
class Mock_Http_Sequence {
	/**
	 * Responses in the sequence.
	 *
	 * @var Mock_Http_Response[]
	 */
	protected array $responses;

	/**
	 * Indicates that invoking this sequence when it is empty should throw an
	 * exception.
	 *
	 * @var bool
	 */
	protected bool $fail_when_empty = true;

	/**
	 * Empty response when the sequence is empty.
	 *
	 * @var Mock_Http_Response|null
	 */
	protected ?Mock_Http_Response $empty_response = null;

	/**
	 * Create a Mock_Http_Sequence instance.
	 *
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * Push a specific response to the sequence
	 *
	 * @param Mock_Http_Response $response Response to push.
	 * @return static
	 */
	public function push( Mock_Http_Response $response ) {
		$this->responses[] = $response;
		return $this;
	}

	/**
	 * Push a response with a specific status code to the sequence.
	 *
	 * @param int   $status Http Status.
	 * @param array $headers Http Headers.
	 * @return static
	 */
	public function push_status( int $status, array $headers = [] ) {
		return $this->push(
			Mock_Http_Response::create()
				->with_response_code( $status )
				->with_headers( $headers )
		);
	}

	/**
	 * Make the sequence return a default response when empty.
	 *
	 * @param Mock_Http_Response $response Response to return when empty.
	 * @return static
	 */
	public function when_empty( Mock_Http_Response $response ) {
		$this->fail_when_empty = false;
		$this->empty_response  = $response;
		return $this;
	}

	/**
	 * Don't throw an exception when empty.
	 *
	 * @return static
	 */
	public function dont_fail_when_empty() {
		return $this->when_empty( Mock_Http_Response::create() );
	}

	/**
	 * Indicates if the sequence has any responses remaining.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return empty( $this->responses );
	}

	/**
	 * Get the nex response in the sequence.
	 *
	 * @throws \RuntimeException Thrown on empty sequence.
	 * @return mixed
	 */
	public function __invoke() {
		if ( empty( $this->responses ) ) {
			if ( $this->fail_when_empty ) {
				throw new \RuntimeException( 'No more responses in sequence.' );
			}

			return $this->empty_response;
		}

		return array_shift( $this->responses );
	}
}
