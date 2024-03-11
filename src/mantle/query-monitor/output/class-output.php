<?php
/**
 * Output class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor\Output;

use Mantle\Database\Model\Model;
use QM_Output_Html;

/**
 * Query Monitor Output provider.
 */
class Output extends QM_Output_Html {
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
	public function output(): void {
		$data    = $this->collector->get_data();
		$request = $data['request'];
		$route   = $data['route'];

		$this->before_non_tabular_output();

		echo '<section>';
		echo '<h3>' . esc_html_e( 'Request', 'mantle' ) . '</h3>';
		echo '<p class="qm-ltr"><code>' . $request->getRequestUri() . '</code></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';

		echo '<section>';
		echo '<h3>' . esc_html_e( 'Matched Route', 'mantle' ) . '</h3>';
		echo '<p class="qm-ltr"><code>' . ( $route ? $route->getPath() : 'n/a' ) . '</code></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</section>';

		echo '</div>';

		echo '<div class="qm-boxed qm-boxed-wrap">';

		if ( $route ) {
			echo '<section>';
			echo '<h3>' . esc_html_e( 'All Route Parameters', 'mantle' ) . '</h3>';

			echo '<table>';

			foreach ( $request->get_route_parameters()->all() as $key => $value ) {
				echo '<tr>';
				echo '<td class="qm-ltr"><code>' . esc_html( $key ) . '</code></td>';

				if ( $value instanceof Model ) {
					echo '<td class="qm-ltr"><code>' . esc_html( $value::class ) . ' (' . esc_html( $value->id() ) . ')</code></td>';
				} elseif ( is_object( $value ) ) {
					echo '<td class="qm-ltr"><code>' . esc_html( $value::class ) . '</code></td>';
				} elseif ( is_array( $value ) ) {
					echo '<td class="qm-ltr"><code>' . wp_json_encode( $value ) . '</code></td>';
				} else {
					echo '<td class="qm-ltr"><code>' . esc_html( (string) $value ) . '</code></td>';
				}
				echo '</tr>';
			}

			echo '</table>';
			echo '</section>';
		}

		$this->after_non_tabular_output();
	}

	/**
	 * Output the header table.
	 *
	 * @param array  $headers Array of headers.
	 * @param string $title Title for the panel.
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
}
