<?php
/**
 * Response_Dumper trait file
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.json_encode_json_encode
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 * phpcs:disable PHPCompatibility
 *
 * @package Mantle
 */

namespace Mantle\Testing\Concerns;

use Mantle\Support\HTML;
use Mantle\Testing\Utils;
use WP_Post;
use WP_Post_Type;
use WP_Term;
use WP_User;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\data_get;
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
	 *
	 * @param string|null $selector Selector to limit HTML content to, optional.
	 *                              For HTML responses, the selector will be a
	 *                              query selector. JSON responses use dot
	 *                              notation.
	 */
	public function dump( ?string $selector = null ): static {
		$this->dump_request();
		render( '<hr />' );
		$this->dump_headers();
		$this->dump_content( $selector );
		$this->dump_query();

		return $this;
	}

	/**
	 * Dump the request and response headers without the content.
	 */
	public function dump_without_content(): static {
		$this->dump_request();
		$this->dump_headers();
		$this->dump_query();

		return $this;
	}

	/**
	 * Dump the request information.
	 */
	public function dump_request(): static {
		if ( ! isset( $this->request ) ) {
			dump( 'No request information available.' );

			return $this;
		}

		// Request information.
		$request_headers = $this->compile_data_table( $this->request->headers->all(), 'Header' ); // @phpstan-ignore-line
		$request_body    = '';

		if ( $this->request->is_json() ) {
			$request_body = json_encode( $this->request->all(), JSON_PRETTY_PRINT );

			$request_body = <<<HTML
				<h3 class="font-bold">Request Body</h3>
				<code>{$request_body}</code>
			HTML;
		} else {
			$request_body = empty( $this->request->all() )
				? '<em class="pt-1">No request body.</em>'
				: $this->compile_data_table( $this->request->all() );

			$request_body = <<<HTML
				<h3 class="font-bold">Request Body</h3>
				{$request_body}
			HTML;
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
							<b class="mr-1">{$this->get_request()?->getMethod()}</b>

							<i>{$this->get_request()?->getUri()}</i>
						</span>
						<span class="{$status_code_class} px-1 py-0.5">{$status_code}</span>
					</h3>
					<h3 class="font-bold">Request Headers</h3>
					{$request_headers}
					{$request_body}
				</div>
			HTML,
		);

		return $this;
	}

	/**
	 * Dump the response headers in a table.
	 */
	public function dump_headers(): static {
		$response_headers = $this->compile_data_table( $this->headers, 'Header' );

		render(
			<<<HTML
				<div class="space-y-1 my-1">
					<h3 class="font-bold">Response Headers</h3>
					{$response_headers}
				</div>
			HTML,
		);

		return $this;
	}

	/**
	 * Dump the contents of the response to the screen.
	 *
	 * @param string|null $selector Query selector or JSON path to filter the content, optional.
	 */
	public function dump_content( ?string $selector = null ): static {
		$content = (string) $this->get_content();

		if ( str_contains( (string) $this->get_header( 'Content-Type' ), 'application/json' ) ) {
			$json = json_decode( $content );

			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( $selector ) {
					$json = data_get( $json, $selector );
				}

				echo json_encode( $json, JSON_PRETTY_PRINT ) . "\n\n";

				return $this;
			}
		}

		render(
			$selector
				? '<h3 class="font-bold mb-1">Response Content (selector: ' . $selector . ')</h3>'
				: '<h3 class="font-bold mb-1">Response Content</h3>'
		);

		if ( $selector ) {
			$content = HTML::create( $content )->filter( $selector )->to_html();
		}

		if ( empty( trim( $content ) ) ) {
			render( '<em>No response content.</em>' );
		} else {
			dump( $content );
		}

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

	/**
	 * Dump information about the current WP_Query object.
	 */
	public function dump_query(): static {
		$queried_object = get_queried_object();

		if ( ! $queried_object ) {
			$queried_object = '<em>No queried object found.</em>';
		} else {
			$queried_object = match ( $queried_object::class ) {
				WP_Post_Type::class => "#{$queried_object->name} (WP_Post_Type): {$queried_object->label}",
				WP_Post::class => "#{$queried_object->ID} (WP_Post): {$queried_object->post_type} — {$queried_object->post_status}",
				WP_Term::class => "#{$queried_object->term_id} (WP_Term): {$queried_object->name} ({$queried_object->slug})",
				WP_User::class => "#{$queried_object->ID} (WP_User): {$queried_object->display_name}",
				default => $queried_object::class . ' object',
			};
		}

		$conditionals = [
			'true'  => [],
			'false' => [],
		];

		foreach ( Utils::get_query_conditional_tags() as $conditional ) {
			if ( $conditional() ) {
				$conditionals['true'][] = '<span class="text-green-500">' . $conditional . '()</span>';
			} else {
				$conditionals['false'][] = '<span class="text-gray-500">' . $conditional . '()</span>';
			}
		}

		$conditionals['true']  = implode( ' ', $conditionals['true'] );
		$conditionals['false'] = implode( ' ', $conditionals['false'] );

		render(
			<<<HTML
				<div class="space-y-1">
					<div>
						<strong>Queried Object:</strong> {$queried_object}
					</div>
					<div>
						<h4>True Conditionals:</h4>
						<p>{$conditionals['true']}</p>
						<h4>False Conditionals:</h4>
						<p>{$conditionals['false']}</p>
					</div>
				</div>
			HTML
		);

		return $this;
	}

	/**
	 * Dump the queried object and end the script.
	 */
	public function dd_query(): never {
		$this->dump_query();
		echo 'post';

		exit( 1 );
	}
}
