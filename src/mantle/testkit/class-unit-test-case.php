<?php
/**
 * Unit_Test_Case class file
 *
 * phpcs:disable Squiz.Functions.MultiLineFunctionDeclaration.ContentAfterBrace
 *
 * @package Mantle
 */

namespace Mantle\Testkit;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunClassInSeparateProcess;
use PHPUnit\Framework\TestCase as Testing_Test_Case;

/**
 * Unit Test Case.
 *
 * Sets some required defaults for us, such as not preserving global state,
 * and running each class in a separate process. This is required to not
 * have global state mixed with Integration test global state.
 */
#[PreserveGlobalState( false )]
#[RunClassInSeparateProcess]
abstract class Unit_Test_Case extends Testing_Test_Case {}
