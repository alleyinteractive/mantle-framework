<?php

namespace Mantle\Tests\Console;

use Mantle\Console\Command;
use Mantle\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class Test_Command extends TestCase {

	public function test_registering_command_with_just_name() {
		$command = new class extends Command {
			protected $name = 'foo:bar';
		};

		$this->assertSame( 'foo:bar', $command->getName() );
	}

	public function test_command_argument_and_option_registration() {
		$command = new class extends Command {
			protected $signature = 'foo:bar {arg1} {arg2?} {--opt1} {--opt2=} {--opt3=*}';
		};

		$this->assertSame( 'foo:bar', $command->getName() );
		$this->assertSame( [ 'arg1', 'arg2' ], array_keys( $command->getDefinition()->getArguments() ) );
		$this->assertSame( [ 'opt1', 'opt2', 'opt3' ], array_keys( $command->getDefinition()->getOptions() ) );
		$this->assertEquals( 1, $command->getDefinition()->getArgumentRequiredCount() );
	}

	public function test_command_argument_and_option_reading() {
		$command = new class extends Command {
			protected $signature = 'foo:bar {arg1} {arg2?} {--opt1} {--opt2=} {--opt3=*}';

			public function __invoke() { }
		};

		$command->set_container( new Container() );

		$command->run( new ArrayInput( [
			'arg1' => 'value1',
			'arg2' => 'value2',
			'--opt1' => true,
			'--opt2' => 'value3',
			'--opt3' => [ 'value4', 'value5' ],
		] ), new NullOutput() );

		$this->assertTrue( $command->has_argument( 'arg1' ) );
		$this->assertEquals(
			[
				'arg1' => 'value1',
				'arg2' => 'value2',
			],
			$command->arguments(),
		);

		$this->assertEquals( 'value1', $command->argument( 'arg1' ) );
		$this->assertEquals( 'value2', $command->argument( 'arg2' ) );
		$this->assertEquals( true, $command->option( 'opt1' ) );
		$this->assertEquals( 'value3', $command->option( 'opt2' ) );
		$this->assertEquals( [ 'value4', 'value5' ], $command->option( 'opt3' ) );
	}

	public function test_command_invalid_arguments() {
		$this->expectException( \InvalidArgumentException::class );

		$command = new class extends Command {
			protected $signature = 'foo:bar {arg1}';
		};

		$command->set_container( new Container() );

		$command->run( new ArrayInput( [
			'arg1' => 'value1',
			'arg2' => 'value2',
		] ), new NullOutput() );
	}

	public function test_command_hidden() {
		$command = new class extends Command {
			protected $signature = 'foo:bar';

			public function parentIsHidden(): bool {
				return parent::isHidden();
			}
		};

		$this->assertFalse( $command->isHidden() );
		$this->assertFalse( $command->parentIsHidden() );

		$command->setHidden( true );

		$this->assertTrue( $command->isHidden() );
		$this->assertTrue( $command->parentIsHidden() );
	}
}
