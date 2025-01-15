<?php
/**
 * Response_Dumper trait file
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use function Mantle\Support\Helpers\collect;
use function Termwind\render;

/**
 * Trait for dumping response information.
 *
 * @mixin \Mantle\Testing\Test_Response
 */
trait Response_Dumper {
	/**
	 * Dump a debug view of a request.
	 *
	 * The debug should include the request information and the response's status
	 * code, headers, and content.
	 */
	public function dump(): static {
		if ( ! isset( $this->request ) ) {
			dump( 'No request information available.' );

			return $this;
		}

		// Request information.
		$request_headers = $this->compile_data_table( $this->request->headers->all(), 'Header' );
		$request_body    = '';

		if ( $this->request->is_json() ) {
			$request_body = json_encode( $this->request->all(), JSON_PRETTY_PRINT );

			$request_body = <<<HTML
				<h3 class="font-bold">Request Body</h3>
				<code>{$request_body}</code>
			HTML;
		} else {
			$request_body = ! empty( $this->request->all() )
				? $this->compile_data_table( $this->request->all() )
				: '<em class="pt-1">No request body.</em>';

			$request_body = <<<HTML
				<h3 class="font-bold">Request Body</h3>
				{$request_body}
			HTML;
		}

		// Response information.
		$response_headers = $this->compile_data_table( $this->headers, 'Header' );
		$response_content = $this->get_content();

		if ( str_contains( $this->get_header( 'Content-Type' ), 'application/json' ) ) {
			$json = json_decode( (string) $response_content );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				$response_content = json_encode( $json, JSON_PRETTY_PRINT );
			}
		} else {
			// Escape the HTML content so it isn't parsed by termwind.
			$response_content = esc_html( $response_content );
		}

		$status_code       = $this->get_status_code();
		$status_code_class = match ( true ) {
			$status_code >= 200 && $status_code < 300 => 'bg-green-300 text-green-700',
			$status_code >= 300 && $status_code < 400 => 'bg-blue-200 text-blue-800',
			$status_code >= 400 && $status_code < 500 => 'bg-yellow-300 text-yellow-700',
			$status_code >= 500 => 'bg-red-300 text-red-800',
			default => 'bg-gray-100 text-gray-800',
		};

		render(
			<<<HTML
				<div class="space-y-1 my-1">
					<h3>
						<span class="mr-1">
							<b class="mr-1">{$this->get_request()->getMethod()}</b>

							<i>{$this->get_request()->getUri()}</i>
						</span>
						<span class="{$status_code_class} px-1 py-0.5">{$status_code}</span>
					</h3>
					<h3 class="font-bold">Request Headers</h3>
					{$request_headers}
					{$request_body}
					<hr />
					<h3 class="font-bold">Response Headers</h3>
					{$response_headers}
					<h3 class="font-bold">Response Content</h3>
					<code>{$response_content}</code>
				</div>
			HTML,
		);

		return $this;
	}

	/**
	 * Dump the contents of the response to the screen.
	 */
	public function dump_content(): static {
		$content = $this->get_content();

		if ( str_contains( $this->get_header( 'Content-Type' ), 'application/json' ) ) {
			$json = json_decode( (string) $content );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				$content = json_encode( $json, JSON_PRETTY_PRINT );
			}
		}

		dump( $content );

		return $this;
	}

	/**
	 * Dump the headers of the response to the screen.
	 */
	public function dump_headers(): static {
		dump( $this->headers );

		return $this;
	}

	/**
	 * Camel-case alias to dump_headers().
	 */
	public function dumpHeaders(): static {
		return $this->dump_headers();
	}

	/**
	 * Dump the JSON, optionally by path, to the screen.
	 *
	 * @param string|null $path
	 */
	public function dump_json( ?string $path = null ): static {
		dump( $this->json( $path ) );

		return $this;
	}

	/**
	 * Camel-case alias to dump_json().
	 *
	 * @param string|null $path
	 */
	public function dumpJson( ?string $path = null ): static {
		return $this->dump_json( $path );
	}

	/**
	 * Dump the content from the response and end the script.
	 */
	public function dd(): never {
		$this->dump();

		exit( 1 );
	}

	/**
	 * Dump the headers from the response and end the script.
	 */
	public function dd_headers(): never {
		$this->dump_headers();

		exit( 1 );
	}

	/**
	 * Camel-case alias to dd_headers().
	 */
	public function ddHeaders(): never {
		$this->dd_headers();
	}

	/**
	 * Dump the JSON from the response and end the script.
	 *
	 * @param string|null $path
	 */
	public function dd_json( ?string $path = null ): never {
		$this->dump_json( $path );

		exit( 1 );
	}

	/**
	 * Camel-case alias to dd_json().
	 *
	 * @param string|null $path
	 */
	public function ddJson( ?string $path = null ): never {
		$this->dd_json( $path );
	}

	/**
	 * Dump the content from the response and end the script.
	 */
	public function dd_content(): never {
		$this->dump_content();

		exit( 1 );
	}

	/**
	 * Camel-case alias to dd_content().
	 */
	public function ddContent(): never {
		$this->dd_content();
	}

	/**
	 * Compile headers and other data types for output in the dumped response.
	 *
	 * @param array<string, string|array<string>> $data Data to compile.
	 * @param string                              $label Label for the data.
	 */
	private function compile_data_table( array $data, string $label = 'Key' ): string {
		$data = collect( $data )->reduce(
			function ( $carry, $value, $key ) {
				if ( ! is_array( $value ) ) {
					$value = [ $value ];
				}

				foreach ( $value as $item ) {
					$carry[] = "<tr><th>{$key}</th><td>{$item}</td></tr>";
				}

				return $carry;
			},
			collect(),
		)->implode( '' );

		return <<<HTML
			<table>
				<thead>
					<tr>
						<th>{$label}</th>
						<th>Value</th>
					</tr>
				</thead>
				<tbody>
					{$data}
				</tbody>
			</table>
		HTML;
	}
}
