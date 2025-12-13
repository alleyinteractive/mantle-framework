<?php
/**
 * Output_Remote_Request class file.
 *
 * @package Mantle
 */

namespace Mantle\Query_Monitor\Output;

use Mantle\Query_Monitor\Collector\Remote_Request_Collector;
use Spatie\Backtrace\Frame;

use function Mantle\Support\Helpers\classname;
use function Mantle\Support\Helpers\collect;

/**
 * Query Monitor output for logs
 */
class Output_Remote_Request extends \QM_Output_Html {
	/**
	 * Collector instance.
	 *
	 * @var Remote_Request_Collector
	 */
	protected $collector;

	/**
	 * Constructor.
	 *
	 * @param Remote_Request_Collector $collector
	 */
	public function __construct( Remote_Request_Collector $collector ) {
		parent::__construct( $collector );

		add_filter( 'qm/output/panel_menus', [ $this, 'panel_menu' ], 20 );
	}

	/**
	 * Get the name for the output.
	 *
	 * @return string
	 */
	public function name() {
		return \__( 'Mantle Remote Requests', 'mantle' );
	}

	/**
	 * Output for the Query Monitor panel.
	 */
	public function output(): void {
		$collector = $this->collector;

		assert( $collector instanceof Remote_Request_Collector );

		$requests = $collector->get_data()->requests;

		if ( empty( $requests ) ) {
			$this->before_non_tabular_output();

			echo $this->build_notice( __( 'No remote requests logged.', 'mantle' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			$this->after_non_tabular_output();
			return;
		}

		$this->before_tabular_output();

		$headers = [
			'method'  => __( 'Method', 'mantle' ),
			'status'  => __( 'Status', 'mantle' ),
			'url'     => __( 'URL', 'mantle' ),
			'caching' => __( 'Caching', 'mantle' ),
			'caller'  => __( 'Caller', 'mantle' ),
			'time'    => __( 'Time', 'mantle' ),
		];

		echo '<thead>';
		echo '<tr>';

		foreach ( $headers as $key => $header ) {
			$class = classname( [
				'qm-num' => $key === 'time',
			] );

			echo "<th scope=\"col\" class=\"{$class}\">" . esc_html( $header ) . '</th>';
		}

		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';

		foreach ( $requests as $request ) {
			echo '<tr>';
			echo '<td>' . esc_html( strtoupper( $request['args']['method'] ?? 'GET' ) ) . '</td>';
			echo '<td class="qm-ltr"><code>';
			echo esc_html( $request['response']->status() );
			echo '</code></td>';
			echo '<td class="qm-ltr"><code>' . esc_html( $request['url'] ) . '</code></td>';
			// echo '<td>' . ( $request['response']->is_from_cache() ? esc_html__( 'Yes', 'mantle' ) : esc_html__( 'No', 'mantle' ) ) . '</td>';
			echo '<td></td>'; // Caching placeholder.

			$caller = array_shift( $request['trace'] );

			echo '<td class="qm-has-toggle qm-nowrap qm-ltr">';

			if ( ! empty( $request['trace'] ) ) {
				echo self::build_toggler();
			}

			echo '<ol>';

			echo '<li>' . $this->compile_trace_frame( $caller ) . '</li>';

			if ( ! empty( $request['trace'] ) ) {
				echo '<div class="qm-toggled"><li>';

				echo collect( $request['trace'] )
					->map( $this->compile_trace_frame( ... ) )
					->implode( '</li><li>' );

				echo '</li></div>';
			}

			echo '</ol></td>';

			echo '<td class="qm-num">' . esc_html( number_format_i18n( ( $request['stop'] - $request['start'] ) * 1000, 2 ) ) . ' ms</td>';
			echo '</tr>';
		}

		$this->after_tabular_output();
	}

	/**
	 * Setup the panel menu.
	 *
	 * @param array<mixed> $menu Panel menu.
	 */
	public function panel_menu( array $menu ): array {
		if ( ! isset( $menu['qm-mantle'] ) ) {
			return $menu;
		}

		$data = $this->collector->get_data();

		$count = count( $data->requests );

		$menu['qm-mantle']['children'][] = $this->menu( [
			'title' => esc_html( $count
				? sprintf( /* translators: %s: Number of remote requests. */
					__( 'Remote Requests (%s)', 'mantle' ),
					number_format_i18n( $count )
				)
				: __( 'Remote Requests', 'mantle' ) ),
		] );

		return $menu;
	}

	/**
	 * Output a trace frame.
	 *
	 * @param Frame $frame Backtrace frame.
	 */
	private function compile_trace_frame( Frame $frame ): string {
		return sprintf(
			'<code>%s</code><br /><span class="qm-info qm-supplemental">%s:%s</span>',
			$frame->method,
			$frame->file,
			$frame->lineNumber,
		);
	}
}
