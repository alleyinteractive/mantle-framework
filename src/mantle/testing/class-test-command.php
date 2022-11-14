<?php
/**
 * Test_Command class file
 *
 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
 *
 * @package Mantle
 */

namespace Mantle\Testing;

use InvalidArgumentException;
use Mantle\Console\Command;
use Mantle\Contracts\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Faux "PendingCommand" class for unit testing.
 *
 * Used for assertions against when testing console commands.
 */
class Test_Command {
	/**
	 * Instance of the Command Tester.
	 *
	 * @var CommandTester
	 */
	protected CommandTester $tester;

	/**
	 * Flag if the command has been executed.
	 *
	 * @var boolean
	 */
	protected bool $has_executed = false;

	/**
	 * All of the expected output lines.
	 *
	 * @var array
	 */
	public array $expected_output = [];

	/**
	 * All of the output lines that aren't expected to be displayed.
	 *
	 * @var array
	 */
	public array $unexpected_output = [];

	/**
	 * Expected exit code.
	 *
	 * @var int
	 */
	public ?int $expected_exit_code = null;

	/**
	 * Constructor.
	 *
	 * @param TestCase|\Mantle\Testing\Concerns\Interacts_With_Console $test
	 * @param Application                                              $app
	 * @param string                                                   $command
	 * @param array                                                    $arguments
	 */
	public function __construct(
		protected TestCase $test,
		protected Application $app,
		protected string $command,
		protected array $arguments = [],
	) {
		$this->verify_command();
	}

	/**
	 * Add expected output.
	 *
	 * @param string $output
	 * @return static
	 */
	public function assertOutputContains( string $output ): static {
		$this->expected_output[] = $output;

		return $this;
	}

	/**
	 * Add unexpected output.
	 *
	 * @param string $output
	 * @return static
	 */
	public function assertOutputNotContains( string $output ): static {
		$this->unexpected_output[] = $output;

		return $this;
	}

	/**
	 * Add an assertion for a specific exit code.
	 *
	 * @param int $code
	 * @return static
	 */
	public function assertExitCode( int $code ): static {
		if ( $this->has_executed ) {
			Assert::assertSame( $code, $this->tester->getStatusCode() );
			return $this;
		}

		$this->expected_exit_code = $code;

		return $this;
	}

	/**
	 * Assert that a command was successful.
	 *
	 * @return static
	 */
	public function assertSuccessful(): static {
		return $this->assertExitCode( Command::SUCCESS );
	}

	/**
	 * Assert that a command was OK.
	 *
	 * @return static
	 */
	public function assertOk(): static {
		return $this->assertSuccessful();
	}

	/**
	 * Assert that a command failed.
	 *
	 * @return static
	 */
	public function assertFailed(): static {
		return $this->assertExitCode( Command::FAILURE );
	}

	/**
	 * Assert that a command was unsuccessful.
	 *
	 * @return static
	 */
	public function assertNotSuccessful(): static {
		return $this->assertFailed();
	}

	/**
	 * Dump the output of the command.
	 */
	public function dd(): never {
		if ( ! $this->has_executed ) {
			$this->run();
		}

		dd( $this->tester->getDisplay() );
	}

	/**
	 * Execute the command.
	 *
	 * @return static
	 */
	public function execute(): static {
		return $this->run();
	}

	/**
	 * Retrieve the Command Tester instance.
	 *
	 * @return CommandTester
	 */
	public function get_tester(): CommandTester {
		return $this->tester;
	}

	/**
	 * Verify the command is formatted properly.
	 *
	 * @throws InvalidArgumentException Thrown on invalid command.
	 * @return void
	 */
	protected function verify_command(): void {
		// Remove 'wp' from the command if passed.
		if ( 0 === strpos( $this->command, 'wp ' ) ) {
			$this->command = substr( $this->command, 3 );
		}

		// Ensure that the command is under the 'mantle' namespace for the time being.
		if ( 0 !== strpos( trim( $this->command ), 'mantle ' ) && 'mantle' !== $this->command ) {
			throw new InvalidArgumentException( 'Command must be prefixed with "mantle" to be tested against.' );
		}

		// Remove the 'mantle' prefix from the command.
		if ( 0 === strpos( $this->command, 'mantle ' ) ) {
			$this->command = substr( $this->command, 7 );
		}
	}

	/**
	 * Run the command.
	 *
	 * @return static
	 */
	public function run(): static {
		$this->has_executed = true;

		try {
			$this->tester = $this->app->make(
				\Mantle\Framework\Console\Kernel::class
			)->test( $this->command, $this->arguments );
		} catch ( CommandNotFoundException $e ) {
			$this->test->fail( "Command [{$this->command}] not found." );
		}

		$this->verify_expectations();

		return $this;
	}

	/**
	 * Verify the expectations after the command has been run.
	 *
	 * @return void
	 */
	protected function verify_expectations(): void {
		// Assert that the exit code matches the expected exit code.
		if ( null !== $this->expected_exit_code ) {
			$exit_code = $this->tester->getStatusCode();

			$this->test->assertEquals(
				$this->expected_exit_code,
				$exit_code,
				"Expected exit code {$this->expected_exit_code} but received {$exit_code}."
			);
		}

		if ( ! empty( $this->expected_output ) ) {
			$output = $this->tester->getDisplay();

			foreach ( $this->expected_output as $expected_output ) {
				$this->test->assertStringContainsString( $expected_output, $output );
			}
		}

		if ( ! empty( $this->unexpected_output ) ) {
			$output = $this->tester->getDisplay();

			foreach ( $this->unexpected_output as $unexpected_output ) {
				$this->test->assertStringNotContainsString( $unexpected_output, $output );
			}
		}

		if ( ! empty( $this->expected_output_substrings ) ) {
			$output = $this->tester->getDisplay();

			foreach ( $this->expected_output_substrings as $expected_output_substring ) {
				$this->test->assertStringContainsString( $expected_output_substring, $output );
			}
		}
	}

	/**
	 * Run the command on variable destruct.
	 */
	public function __destruct() {
		if ( ! $this->has_executed ) {
			$this->run();
		}
	}
}
