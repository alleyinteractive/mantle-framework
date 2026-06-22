<?php
/**
 * Collector class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor\Collector;

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
	 * Constructor.
	 *
	 * @param Application $app Application instance.
	 */
	public function __construct( protected Application $app ) {}

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
	public function process(): void {
		$this->data['request'] = $this->app->make( Request::class );
		$this->data['route']   = $this->data['request']->get_route();
	}
}
