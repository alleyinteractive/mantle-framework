<?php
/**
 * Interacts_With_IO trait file
 *
 * @package Mantle
 */

namespace Mantle\Console\Concerns;

use Closure;
use Mantle\Contracts\Support\Arrayable;
use Mantle\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Mantle\Console\Output_Style;
use Symfony\Component\Console\Helper\TableStyle;

trait Interacts_With_IO {
	/**
	 * The input implementation.
	 */
	protected InputInterface $input;

	/**
	 * Output interface.
	 */
	protected Output_Style $output;

	/**
	 * The mapping between human readable verbosity levels and Symfony's OutputInterface.
	 *
	 * @var array<string, int>
	 */
	protected array $verbosity_map = [
		'v'      => OutputInterface::VERBOSITY_VERBOSE,
		'vv'     => OutputInterface::VERBOSITY_VERY_VERBOSE,
		'vvv'    => OutputInterface::VERBOSITY_DEBUG,
		'quiet'  => OutputInterface::VERBOSITY_QUIET,
		'normal' => OutputInterface::VERBOSITY_NORMAL,
	];

	/**
	 * Determine if the given argument is present.
	 *
	 * @param  string|int $name
	 */
	public function has_argument( string|int $name ): bool {
		return $this->input->hasArgument( $name );
	}

	/**
	 * Get the value of a command argument.
	 *
	 * @param  string|null $key
	 * @return string|mixed
	 */
	public function argument( $key = null ) {
		if ( is_null( $key ) ) {
			return $this->input->getArguments();
		}

		return $this->input->getArgument( $key );
	}

	/**
	 * Get all of the arguments passed to the command.
	 *
	 * @return array<string, string>
	 */
	public function arguments() {
		return $this->input->getArguments();
	}

	/**
	 * Determine if the given option is present.
	 *
	 * @param  string $name
	 * @return bool
	 */
	public function has_option( $name ) {
		return $this->input->hasOption( $name );
	}

	/**
	 * Get the value of a command option.
	 *
	 * @param  string|null $key The option name.
	 * @param  mixed       $default Default value if the option does not exist.
	 * @return string|array<mixed>|bool|null
	 */
	public function option( $key = null, $default = null ) {
		if ( is_null( $key ) ) {
			return $this->input->getOptions();
		}

		return $this->input->getOption( $key ) ?: $default;
	}

	/**
	 * Get all of the options passed to the command.
	 *
	 * @return array<mixed>
	 */
	public function options() {
		return $this->input->getOptions();
	}

	/**
	 * Write a string as question output.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function question( string $string, $verbosity = null ): void {
		$this->line( $string, 'question', $verbosity );
	}

	/**
	 * Confirm a question with the user.
	 *
	 * @param  string $question
	 * @param  bool   $default
	 * @return bool
	 */
	public function confirm( string $question, bool $default = false ) {
		return $this->output->confirm( $question, $default );
	}

	/**
	 * Prompt the user for input.
	 *
	 * @param  string      $question
	 * @param  string|null $default
	 * @return mixed
	 */
	public function ask( $question, $default = null ) {
		return $this->output->ask( $question, $default );
	}

	/**
	 * Prompt the user for input with auto completion.
	 *
	 * @param  string                                       $question
	 * @param  array<string>|array<string, string>|callable $choices
	 * @param  string|null                                  $default
	 */
	public function anticipate( $question, $choices, $default = null ): mixed {
		return $this->ask_with_completion( $question, $choices, $default );
	}

	/**
	 * Prompt the user for input with auto completion.
	 *
	 * @param  string                                       $question
	 * @param  array<string>|array<string, string>|callable $choices
	 * @param  string|null                                  $default
	 */
	public function ask_with_completion( $question, $choices, $default = null ): mixed {
		$question = new Question( $question, $default );

		is_callable( $choices )
		? $question->setAutocompleterCallback( $choices )
		: $question->setAutocompleterValues( $choices );

		return $this->output->askQuestion( $question );
	}

	/**
	 * Prompt the user for input but hide the answer from the console.
	 *
	 * @param  string $question
	 * @param  bool   $fallback
	 */
	public function secret( string $question, bool $fallback = true ): mixed {
		$question = new Question( $question );

		$question->setHidden( true )->setHiddenFallback( $fallback );

		return $this->output->askQuestion( $question );
	}

	/**
	 * Give the user a single choice from an array of answers.
	 *
	 * @param  string          $question
	 * @param  array<string>   $choices
	 * @param  string|int|null $default
	 * @param  int|null        $attempts
	 * @param  bool            $multiple
	 */
	public function choice( string $question, array $choices, mixed $default = null, ?int $attempts = null, bool $multiple = false ): mixed {
		$question = new ChoiceQuestion( $question, $choices, $default );

		$question->setMaxAttempts( $attempts )->setMultiselect( $multiple );

		return $this->output->askQuestion( $question );
	}

	/**
	 * Format items into multiple formats based on a flag.
	 *
	 * @param string                                   $format Format to return (json, xml, count, csv, or table).
	 * @param array<string>                            $headers Headers for the table.
	 * @param array<string[]>|Arrayable<int, string[]> $data    Data for the table.
	 */
	public function format_data( string $format, array $headers, array|Arrayable $data ): mixed {
		$data = $data instanceof Arrayable ? $data->to_array() : $data;

		return match ( $format ) {
			'count'  => count( $data ),
			'csv'    => $this->output->format_csv( $headers, $data ),
			'json'   => $this->output->format_json( $headers, $data ),
			'xml'    => $this->output->format_xml( $headers, $data ),
			default  => $this->table( $headers, $data ),
		};
	}

	/**
	 * Format input to textual table.
	 *
	 * @param  array<string>                                       $headers
	 * @param  Arrayable<int, string> |array<string[]>             $rows
	 * @param  \Symfony\Component\Console\Helper\TableStyle|string $table_style
	 * @param  array<mixed>                                        $column_styles
	 */
	public function table( array $headers, array|Arrayable $rows, TableStyle|string $table_style = 'default', array $column_styles = [] ): void {
		$table = new Table( $this->output );

		if ( $rows instanceof Arrayable ) {
			$rows = $rows->to_array();
		}

		$table->setHeaders( $headers )->setRows( $rows )->setStyle( $table_style );

		foreach ( $column_styles as $column_index => $column_style ) {
			$table->setColumnStyle( $column_index, $column_style );
		}

		$table->render();
	}

	/**
	 * Execute a given callback while advancing a progress bar.
	 *
	 * @template TData
	 *
	 * @param  iterable<TData>|int $total_steps
	 * @param  \Closure            $callback
	 * @return iterable<TData>|null
	 */
	public function with_progress_bar( iterable|int $total_steps, Closure $callback ): ?iterable {
		$bar = $this->output->createProgressBar(
			is_iterable( $total_steps ) ? count( $total_steps ) : $total_steps
		);

		$bar->start();

		if ( is_iterable( $total_steps ) ) {
			foreach ( $total_steps as $total_step ) {
				$callback( $total_step, $bar );

				$bar->advance();
			}
		} else {
			$callback( $bar );
		}

		$bar->finish();

		return is_iterable( $total_steps ) ? $total_steps : null;
	}

	/**
	 * Write a string as information output.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function info( $string, $verbosity = null ): void {
		$this->line( $string, 'info', $verbosity );
	}

	/**
	 * Write to the output interface.
	 *
	 * @deprecated Use `line()` instead.
	 *
	 * @param string $message Message to log.
	 */
	public function log( string $message ): void {
		$this->line( $message );
	}

	/**
	 * Colorize a string for output.
	 *
	 * @param string $string String to colorize.
	 * @param string $color Color to use.
	 */
	public function colorize( string $string, string $color ): string {
		return sprintf( '<fg=%s>%s</>', $color, $string );
	}

	/**
	 * Write a string as standard output.
	 *
	 * @param  string          $string
	 * @param  string|null     $style
	 * @param  int|string|null $verbosity
	 */
	public function line( $string, $style = null, $verbosity = null ): void {
		$styled = $style ? "<{$style}>{$string}</{$style}>" : $string;

		$this->output->writeln( $styled, $this->parse_verbosity( $verbosity ) );
	}

	/**
	 * Write a string as comment output.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function comment( $string, $verbosity = null ): void {
		$this->line( $string, 'comment', $verbosity );
	}

	/**
	 * Write a string as error output.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function error( string $string, $verbosity = null ): void {
		$this->line( $string, 'error', $verbosity );
	}

	/**
	 * Write a string as warning output.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function warn( string $string, $verbosity = null ): void {
		if ( ! $this->output->getFormatter()->hasStyle( 'warning' ) ) {
			$style = new OutputFormatterStyle( 'yellow' );

			$this->output->getFormatter()->setStyle( 'warning', $style );
		}

		$this->line( $string, 'warning', $verbosity );
	}

	/**
	 * Write a string as success output.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function success( string $string, $verbosity = null ): void {
		if ( ! $this->output->getFormatter()->hasStyle( 'success' ) ) {
			$style = new OutputFormatterStyle( 'green' );

			$this->output->getFormatter()->setStyle( 'success', $style );
		}

		$this->line( $string, 'success', $verbosity );
	}

	/**
	 * Write a string in an alert box.
	 *
	 * @param  string          $string
	 * @param  int|string|null $verbosity
	 */
	public function alert( string $string, $verbosity = null ): void {
		$length = Str::length( strip_tags( $string ) ) + 12; // phpcs:ignore WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter

		$this->comment( str_repeat( '*', $length ), $verbosity );
		$this->comment( '*     ' . $string . '     *', $verbosity );
		$this->comment( str_repeat( '*', $length ), $verbosity );

		$this->comment( '', $verbosity );
	}

	/**
	 * Write a blank line.
	 *
	 * @param  int $count
	 * @return static
	 */
	public function new_line( $count = 1 ) {
		$this->output->newLine( $count );

		return $this;
	}

	/**
	 * Get the input implementation.
	 */
	public function input(): InputInterface {
		return $this->input;
	}

	/**
	 * Set the input implementation.
	 *
	 * @param InputInterface $input Input.
	 */
	public function set_input( InputInterface $input ): void {
		$this->input = $input;
	}

	/**
	 * Retrieve the output interface.
	 */
	public function output(): OutputInterface|Output_Style {
		return $this->output;
	}

	/**
	 * Set the output implementation.
	 *
	 * @param OutputInterface|Output_Style $output Output interface.
	 */
	public function set_output( OutputInterface|Output_Style $output ): void {
		if ( ! $output instanceof Output_Style ) {
			$output = new Output_Style( $this->input, $output );
		}

		$this->output = $output;
	}

	/**
	 * Get the verbosity level in terms of Symfony's OutputInterface level.
	 *
	 * @param  string|int|null $level
	 */
	protected function parse_verbosity( $level = null ): int {
		if ( isset( $this->verbosity_map[ $level ] ) ) {
			$level = $this->verbosity_map[ $level ];
		} elseif ( ! is_int( $level ) ) {
			$level = OutputInterface::VERBOSITY_NORMAL;
		}

		return $level;
	}
}
