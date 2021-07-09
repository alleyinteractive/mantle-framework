<?php
/**
 * Output_Headers class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor\Output;

use QM_Output_Html;

/**
 * Query Monitor Headers Output
 */
class Output_Headers extends QM_Output_Html {
	/**
	 * Constructor.
	 *
	 * @param \QM_Collector $collector Collector instance.
	 */
	public function __construct( \QM_Collector $collector ) {
		parent::__construct( $collector );

		add_filter( 'qm/output/panel_menus', [ $this, 'panel_menu' ], 20 );
	}

	/**
	 * Get the name for the output.
	 *
	 * @return string
	 */
	public function name() {
		return __( 'Mantle Headers', 'mantle' );
	}

	/**
	 * Output for the Query Monitor panel.
	 */
	public function output() {
		$this->output_request();
		$this->output_response();
	}

	/**
	 * Output the request headers panel.
	 */
	protected function output_request() {
		$data = $this->collector->get_data();

		$this->before_tabular_output();

		$this->output_header_table( $data['request_headers'] ?? [], __( 'Request Header Name', 'mantle' ) );

		$this->after_tabular_output();
	}

	/**
	 * Output the request headers panel.
	 */
	protected function output_response() {
		$data = $this->collector->get_data();

		$this->before_tabular_output( sprintf( 'qm-%s-response', $this->collector->id ) );

		$this->output_header_table( $data['response_headers'] ?? [], __( 'Response Header Name', 'mantle' ) );

		$this->after_tabular_output();
	}

	/**
	 * Output the response headers panel.
	 *
	 * @param array  $headers Array of headers.
	 * @param string $title Panel title.
	 */
	protected function output_header_table( array $headers, $title ) {
		echo '<thead>';
		echo '<tr>';
		echo '<th>';
		echo esc_html( $title );
		echo '</th><th>';
		esc_html_e( 'Value', 'mantle' );
		echo '</th></tr>';
		echo '<tbody>';

		foreach ( $headers as $name => $value ) {
			if ( is_array( $value ) ) {
				$value = array_shift( $value );
			}

			echo '<tr>';
			$formatted = str_replace( ' ', '-', ucwords( strtolower( str_replace( [ '-', '_' ], ' ', $name ) ) ) );
			printf( '<th scope="row"><code>%s</code></th>', esc_html( $formatted ) );
			printf( '<td><pre class="qm-pre-wrap"><code>%s</code></pre></td>', esc_html( $value ) );
			echo '</tr>';
		}

		echo '</tbody>';

		echo '<tfoot>';
		echo '<tr>';
		echo '<td colspan="2">';
		esc_html_e( 'Note that header names are not case-sensitive.', 'mantle' );
		echo '</td>';
		echo '</tr>';
		echo '</tfoot>';
	}

	/**
	 * Setup the panel menu.
	 *
	 * @param array $menu Panel menu.
	 */
	public function panel_menu( array $menu ) {
		if ( ! isset( $menu['qm-mantle'] ) ) {
			return $menu;
		}

		$ids = [
			'qm-mantle-headers'          => __( 'Request Headers', 'mantle' ),
			'qm-mantle-headers-response' => __( 'Response Headers', 'mantle' ),
		];

		foreach ( $ids as $id => $title ) {
			$menu['qm-mantle']['children'][] = [
				'id'    => $id,
				'href'  => '#' . $id,
				'title' => esc_html( $title ),
			];
		}

		return $menu;
	}
}
