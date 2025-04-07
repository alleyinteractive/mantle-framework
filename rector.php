<?php
/**
 * Rector Configuration
 *
 * phpcs:disable
 */

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;

use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\Empty_\EmptyOnNullableObjectToInstanceOfRector;
use Rector\ValueObject\PhpVersion;

/**
 * Rector Configuration
 *
 * Overtime this file will have more rules enabled for it. Right now, most of
 * them are commented out. They will be spread out over multiple pull requests.
 *
 * Rules that are known to cause issues and should not be used:
 *
 * - RemoveUselessParamTagRector: Conflicts with WordPress Coding Standards
 * - ChangeOrIfContinueToMultiContinueRector: doesn't make sense.
 */
return RectorConfig::configure()
	->withPhpVersion( PhpVersion::PHP_84 )
	->withPhpSets()
	->withIndent( "\t" )
	->withPaths( [ __DIR__ . '/src' ] )
	->withPreparedSets(
		earlyReturn: true,
		deadCode: true,
		instanceOf: true,
	)
	->withTypeCoverageLevel( 18 ) // Out of 49.
	->withRules(
		[
			RenameForeachValueVariableToMatchExprVariableRector::class,
			ExplicitNullableParamTypeRector::class,
		]
	)
	->withSkip( [
		AddVoidReturnTypeWhereNoReturnRector::class => [
			__DIR__ . '/src/mantle/testing/concerns/trait-core-shim.php',
			__DIR__ . '/tests/Testing/CoreTestShimTest.php',
			__DIR__ . '/tests/testing/CoreTestShimTest.php',
		],
		RemoveUselessParamTagRector::class,
		FirstClassCallableRector::class,
		StrContainsRector::class,
		AddArrowFunctionReturnTypeRector::class,
		ChangeOrIfContinueToMultiContinueRector::class,
		RemoveAlwaysElseRector::class,
		EmptyOnNullableObjectToInstanceOfRector::class,
		ReturnBinaryOrToEarlyReturnRector::class => [
			__DIR__ . '/src/mantle/http-client/class-response.php',
		],
		RemoveExtraParametersRector::class => [
			__DIR__ . '/src/mantle/support/helpers/helpers-general.php',
		],
		ReturnTypeFromReturnNewRector::class => [
			__DIR__ . '/src/mantle/support/class-collection.php',
			__DIR__ . '/src/mantle/support/traits/trait-enumerates-values.php',
		],
	] );
