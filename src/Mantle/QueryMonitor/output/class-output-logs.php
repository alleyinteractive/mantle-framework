<?php
/**
 * Output_Logs class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor\Output;

use Mantle\Query_Monitor\Collector\Log_Collector;

/**
 * Query Monitor output for logs
 */
class Output_Logs extends \QM_Output_Html {
	/**
	 * Constructor.
	 *
	 * @param Log_Collector $collector
	 */
	public function __construct( Log_Collector $collector ) {
		parent::__construct( $collector );

		add_filter( 'qm/output/panel_menus', [ $this, 'panel_menu' ], 20 );
	}

	/**
	 * Get the name for the output.
	 *
	 * @return string
	 */
	public function name() {
		return \__( 'Mantle Logs', 'mantle' );
	}

	/**
	 * Output for the Query Monitor panel.
	 */
	public function output(): void {
		$data = $this->collector->get_data();

		if ( empty( $data['logs'] ) ) {
			$this->before_non_tabular_output();

			echo $this->build_notice( __( 'No data logged.', 'mantle' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$this->after_non_tabular_output();

			return;
		}

		$levels = array_map( 'ucfirst', $this->collector->get_levels() );

		$this->before_tabular_output();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'type', $levels, __( 'Level', 'query-monitor' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</th>';
		echo '<th scope="col" class="qm-col-message">' . esc_html__( 'Message', 'query-monitor' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Caller', 'query-monitor' ) . '</th>';
		echo '<th scope="col" class="qm-filterable-column">';
		echo $this->build_filter( 'component', $data['components'], __( 'Component', 'query-monitor' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</th>';
		echo '<th scope="col">' . esc_html__( 'Context', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $data['logs'] as $row ) {
			$component = $row['trace']->get_component();

			$row_attr                      = [];
			$row_attr['data-qm-component'] = $component->name;
			$row_attr['data-qm-type']      = ucfirst( (string) $row['level'] );

			$attr = '';

			foreach ( $row_attr as $a => $v ) {
				$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';
			}

			$is_warning = in_array( $row['level'], $this->collector->get_warning_levels(), true );

			if ( $is_warning ) {
				$class = 'qm-warn';
			} else {
				$class = '';
			}

			echo '<tr' . $attr . ' class="' . esc_attr( $class ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo '<td class="qm-nowrap">';

			if ( $is_warning ) {
				echo '<span class="dashicons dashicons-warning" aria-hidden="true"></span>';
			} else {
				echo '<span class="dashicons" aria-hidden="true"></span>';
			}

			echo esc_html( ucfirst( (string) $row['level'] ) );
			echo '</td>';

			printf(
				'<td><pre>%s</pre></td>',
				esc_html( $row['message'] )
			);

			$stack          = [];
			$filtered_trace = $row['trace']->get_display_trace();

			foreach ( $filtered_trace as $item ) {
				$stack[] = self::output_filename( $item['display'], $item['calling_file'], $item['calling_line'] );
			}

			$caller = array_shift( $stack );

			echo '<td class="qm-has-toggle qm-nowrap qm-ltr">';

			if ( ! empty( $stack ) ) {
				echo self::build_toggler(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '<ol>';

			echo "<li>{$caller}</li>"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( ! empty( $stack ) ) {
				echo '<div class="qm-toggled"><li>' . implode( '</li><li>', $stack ) . '</li></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '</ol></td>';

			printf(
				'<td class="qm-nowrap">%s</td>',
				esc_html( $component->name )
			);

			printf( '<td>%s</td>', esc_html( var_export( $row['context'], true ) ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

			echo '</tr>';

		}

		echo '</tbody>';

		$this->after_tabular_output();
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
			'qm-mantle-logs' => __( 'Logs', 'mantle' ),
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
