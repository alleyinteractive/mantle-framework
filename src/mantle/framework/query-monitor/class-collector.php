<?php
namespace Mantle\Framework\Query_Monitor;

use Mantle\Framework\Contracts\Application;
use Mantle\Framework\Http\Request;

class Collector extends \QM_Collector {
	public $id = 'mantle';

	/**
	 * Application instance.
	 *
	 * @var Application
	 */
	protected $app;

	public function __construct( Application $app ) {
		$this->app = $app;
	}

	public function process() {
		$this->data['request'] = $this->app->make( Request::class );
		$this->data['route'] = $this->data['request']->get_route();
		// dd($this->data['route']);
		return $this->data;
		dd($this->data);
	}
	// public function get_data() {
	// 	return [

	// 	]
	// }
}
