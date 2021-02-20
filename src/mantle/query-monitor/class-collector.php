<?php
/**
 * Collector class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor;

use Mantle\Contracts\Application;
use Mantle\Http\Request;

/**
 * Data Collector
 */
class Collector extends \QM_Collector {
	/**
	 * Collector ID
	 *
	 * @var string
	 */
	public $id = 'mantle';

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
		return 'Mantle Collector';
	}

	/**
	 * Process the current request.
	 */
	public function process() {
		$this->data['request'] = $this->app->make( Request::class );
		$this->data['route']   = $this->data['request']->get_route();
	}
}
