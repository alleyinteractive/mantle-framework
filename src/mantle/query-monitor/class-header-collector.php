<?php
/**
 * Header_Collector class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor;

use Mantle\Contracts\Application;
use Mantle\Http\Request;

/**
 * Header Collector
 */
class Header_Collector extends \QM_Collector {
	/**
	 * Collector ID
	 *
	 * @var string
	 */
	public $id = 'mantle-headers';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( Application $app ) {
		$this->app = $app;
	}

	/**
	 * Collector name.
	 *
	 * @return string
	 */
	public function name() {
		return 'Mantle Header Collector';
	}

	/**
	 * Setup the collector data.
	 */
	public function process() {
		$request = $this->app->make( Request::class );
		if ( $request ) {
			$this->data['request_headers'] = $request->headers->all();
		}

		try {
			$response = $this->app['response'];
		} catch ( \Throwable $e ) {
			unset( $e );
			$response = null;
		}

		if ( $response ) {
			$this->data['response_headers'] = $response->headers->all();
		}
	}
}
