<?php
/**
 * Runner class file
 *
 * @package Mantle
 */

namespace Mantle\Testing\Parallel;

use Closure;
use Mantle\Container\Container;
use Mantle\Contracts\Application;
use Mantle\Facade\Parallel_Testing;
use Mantle\Framework\Bootloader;
use ParaTest\RunnerInterface;
use PHPUnit\Runner\Version;
use PHPUnit\TextUI\Configuration\PhpHandler;
use RuntimeException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function Mantle\Support\Helpers\collect;
use function Mantle\Support\Helpers\tap;

/**
 * Parallel Runner
 *
 * This may not be needed.
 */
class Runner implements RunnerInterface {
	/**
	 * The application resolver callback.
	 */
	protected static Closure|null $application_resolver = null;

	/**
	 * The runner resolver callback.
	 */
	protected static Closure|null $runner_resolver = null;

	/**
	 * The output instance.
	 */
	protected OutputInterface $output;

	/**
	 * The original test runner.
	 */
	protected RunnerInterface $runner;

	/**
	 * Creates a new test runner instance.
	 *
	 * @param  \ParaTest\Options                                 $options
	 * @param  \Symfony\Component\Console\Output\OutputInterface $output
	 * @return void
	 */
	public function __construct( protected \ParaTest\Options $options, OutputInterface $output ) {
		if ( ! version_compare( Version::id(), '10.0.0', '>=' ) ) {
			throw new RuntimeException( 'PHPUnit 10.0.0 or greater is required to run tests in parallel with Mantle.' );
		}

		if ( $output instanceof ConsoleOutput ) {
			$output = new Console_Output( $output );
		}

		$runner_resolver = static::$runner_resolver ?: fn ( $options, OutputInterface $output ) => new \ParaTest\WrapperRunner\WrapperRunner( $options, $output );

		$this->runner = $runner_resolver( $options, $output );
	}

	/**
	 * Set the application resolver callback.
	 *
	 * @param  \Closure|null $resolver
	 */
	public static function resolve_application_using( Closure|null $resolver ): void {
		static::$application_resolver = $resolver;
	}

	/**
	 * Set the runner resolver callback.
	 *
	 * @param  \Closure|null $resolver
	 */
	public static function resolve_runner_using( Closure|null $resolver ): void {
		static::$runner_resolver = $resolver;
	}

	/**
	 * Returns the highest exit code encountered throughout the course of test execution.
	 *
	 * @return int
	 */
	// public function getExitCode(): int {
	// 	return $this->runner->getExitCode();
	// }

	/**
	 * Apply the given callback for each process.
	 *
	 * @param  callable $callback
	 * @return void
	 */
	protected function each_process( callable $callback ): void {
		collect( range( 1, $this->options->processes ) )->each(
			function ( $token ) use ( $callback ) {
				tap(
					Container::get_instance(),
					// $this->create_application(),
					function ( $app ) use ( $callback, $token ) {
						Parallel_Testing::resolve_token_using( fn () => $token );

						$callback( $app );
					}
				)->flush();
			}
		);
	}

	/**
	 * Creates the application.
	 *
	 * @return \Illuminate\Contracts\Foundation\Application
	 *
	 * @throws \RuntimeException
	 */
	protected function create_application(): Application {
		$bootloader = Bootloader::instance();

		$bootloader->make( \Mantle\Framework\Http\Kernel::class )->bootstrap();

		return $bootloader->get_application();

		// dd($kernel);
		// dd( 'create app' );
		// $applicationResolver = static::$application_resolver ?: function () {
		// 	if ( trait_exists( \Tests\CreatesApplication::class ) ) {
		// 		$applicationCreator = new class()
		// 		{
		// 				use \Tests\CreatesApplication;
		// 		};

		// 		return $applicationCreator->createApplication();
		// 	} elseif ( file_exists( $path = ( Application::inferBasePath() . '/bootstrap/app.php' ) ) ) {
		// 		$app = require $path;

		// 		$app->make( Kernel::class )->bootstrap();

		// 		return $app;
		// 	}

		// 	throw new RuntimeException( 'Parallel Runner unable to resolve application.' );
		// };

		// return $applicationResolver();
	}

	/**
	 * Execute the parallel runner.
	 *
	 * @return int
	 */
	public function run(): int {
		$configuration = $this->options->configuration;

		( new PhpHandler() )->handle( $configuration->php() );


		// Create the application with bootloader.
		// TODO: make this use bootstrap/app.php if exists.
		Bootloader::instance();

		$this->each_process( fn () => Parallel_Testing::call_set_up_process_callbacks() );

		return $this->runner->run();
		// $result = $this->runner->run();

		// try {
		// } finally {
		// 	$this->each_process( fn () => Parallel_Testing::call_tear_down_process_callbacks() );
		// }


		// return $result;
	}
}
