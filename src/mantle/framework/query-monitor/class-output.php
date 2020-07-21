<?php
/**
 * Output class file.
 *
 * @package Mantle
 */

namespace Mantle\Framework\Query_Monitor;

use QM_Output_Html;

/**
 * Query Monitor Output provider.
 */
class Output extends QM_Output_Html {

	/**
	 * Collector instance.
	 *
	 * @var \QM_Collector_Request Collector.
	 */
	// protected $collector;

	/**
	 * Constructor.
	 *
	 * @param \QM_Collector $collector Collector instance.
	 */
	public function __construct( \QM_Collector $collector ) {
		parent::__construct( $collector );

		\add_filter( 'qm/output/menus', [ $this, 'admin_menu' ], 50 );
	}

	/**
	 * Get the name for the output.
	 *
	 * @return string
	 */
	public function name() {
		return \__( 'Mantle', 'mantle' );
	}

	/**
	 * Output for the Query Monitor panel.
	 */
	public function output() {
		$data = $this->collector->get_data();

		/**
		 * @var \Mantle\Framework\Http\Request
		 */
		$request = $data['request'];

		/**
		 * @var \Mantle\Framework\Http\Routing\Route
		 */
		$route = $data['route'];
		// // dd($route);
		// // dd($data);
		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>' . esc_html_e( 'Request', 'mantle' ) . '</h3>';
		echo '<p class="qm-ltr"><code>' . $request->getRequestUri() . '</code></p>'; // WPCS: XSS ok.
		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html_e( 'Matched Route', 'mantle' ) . '</h3>';
		echo '<p class="qm-ltr"><code>' . ( $route ? $route->getPath() :  'n/a' ) . '</code></p>'; // WPCS: XSS ok.
		echo '</section>';

		$this->after_non_tabular_output();
	}
}
