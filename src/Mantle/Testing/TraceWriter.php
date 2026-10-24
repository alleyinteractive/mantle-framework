<?php
/**
 * TraceWriter class file
 *
 * @package Mantle
 */

declare(strict_types=1);

namespace Mantle\Testing;

use NunoMaduro\Collision\Highlighter;
use Spatie\Backtrace\Frame;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function Mantle\Support\Helpers\collect;
use function Termwind\render;
use function Termwind\renderUsing;

/**
 * Output traces in console and unit testing environments.
 *
 * @internal
 */
class TraceWriter {
	/**
	 * Number of lines to show in code snippets.
	 */
	private readonly int $lines;

	/**
	 * Constructor.
	 *
	 * @param string          $title
	 * @param string          $description
	 * @param Frame[]         $frames
	 * @param string|null     $prefix
	 * @param OutputInterface $output
	 */
	public function __construct(
		public readonly string $title,
		public readonly string $description,
		public readonly array $frames,
		public readonly ?string $prefix = null,
		private readonly OutputInterface $output = new ConsoleOutput( verbosity: OutputInterface::VERBOSITY_NORMAL, decorated: true ),
	) {
		$this->lines = Utils::env( 'MANTLE_TESTING_TRACE_SNIPPET_LINES', 10 );
	}

	/**
	 * Write the trace to the output.
	 */
	public function write(): void {
		renderUsing( $this->output );

		$this->renderTitleAndDescription();
		$this->renderSnippet();
		$this->renderTrace();

		renderUsing( null );
	}

	/**
	 * Render the title of the trace.
	 */
	private function renderTitleAndDescription(): void {
		if ( $this->prefix ) {
			$prefix = strtoupper( $this->prefix );
			$prefix = "<span class=\"bg-red-400 text-black font-bold px-1\">{$prefix}</span> ";
		} else {
			$prefix = '';
		}

		$description = strip_tags( $this->description );

		render(
			<<<HTML
			<div class="mx-1 mt-1">
				{$prefix}<span class="font-bold">{$this->title}</span>
				<div class="my-1">{$description}</div>
			</div>
			HTML
		);
	}

	/**
	 * Render the code snippet of the first frame.
	 */
	private function renderSnippet(): void {
		$frame = $this->getFirstFrame();

		assert( $frame instanceof Frame );

		if ( class_exists( Highlighter::class ) && method_exists( Highlighter::class, 'getCodeSnippet' ) ) {
			$highlighter = new Highlighter();

			$this->output->writeln( $highlighter->getCodeSnippet(
				source: (string) file_get_contents( $frame->file ),
				lineNumber: $frame->lineNumber,
				linesBefore: (int) floor( $this->lines / 2 ),
				linesAfter: (int) ceil( $this->lines / 2 ) - 1,
			) );
		} else {
			$starting_line = $this->getStartingLine( $frame, $this->lines );
			$snippet       = collect( $frame->getSnippet( $this->lines ) )
				->values()
				->implode( PHP_EOL );

			render( "<code start-line=\"{$starting_line}\" line=\"{$frame->lineNumber}\">{$snippet}</code>" );
		}
	}

	/**
	 * Render the trace frames.
	 */
	private function renderTrace(): void {
		foreach ( $this->frames as $i => $frame ) {
			// Skip vendor frames unless in verbose mode.
			if ( $this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE && strpos( $frame->file, '/vendor/' ) !== false ) {
				continue;
			}

			$pos  = str_pad( (string) ( (int) $i + 1 ), 4, ' ' );
			$file = $this->getFileRelativePath( $frame->file );

			$this->output->writeln(
				"<fg=yellow>{$pos}</><fg=default;options=bold>{$file}</>:<fg=default;options=bold>{$frame->lineNumber}</>",
			);
		}

		$this->output->writeln( '' );
	}

	/**
	 * Get the starting line for a code snippet.
	 *
	 * @param Frame $frame
	 * @param int   $lines
	 */
	private function getStartingLine( Frame $frame, int $lines ): int {
		$starting_line = $frame->lineNumber - (int) floor( $lines / 2 );

		return max( 1, $starting_line );
	}

	/**
	 * Get the first frame from the trace.
	 */
	private function getFirstFrame(): ?Frame {
		return collect( $this->frames )->first();
	}

	/**
	 * Returns the relative path of the given file path.
	 *
	 * @param string $filePath Absolute file path.
	 */
	private function getFileRelativePath( string $filePath ): string {
		$cwd = (string) getcwd();

		if ( ! empty( $cwd ) ) {
			return str_replace( $cwd . DIRECTORY_SEPARATOR, '', $filePath );
		}

		return $filePath;
	}
}
