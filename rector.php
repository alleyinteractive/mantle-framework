<?php
/**
 * Rector Configuration
 *
 * phpcs:disable
 */

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\Contract\Rector\RectorInterface;
use Rector\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector;
use Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\BooleanAnd\RemoveAndTrueRector;
use Rector\DeadCode\Rector\Cast\RecastingRemovalRector;
use Rector\DeadCode\Rector\ClassConst\RemoveUnusedPrivateClassConstantRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedConstructorParamRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;

use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
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
	->withPhpVersion( PhpVersion::PHP_82)
	->withPhpSets()
	->withIndent( "\t" )
	->withPaths( [ __DIR__ . '/src' ] )
	->withPreparedSets(
		earlyReturn: true,
		deadCode: true,
		instanceOf: true,
	)
	->withTypeCoverageLevel(10)
	->withRules(
		[
			AddVoidReturnTypeWhereNoReturnRector::class,
			RenameForeachValueVariableToMatchExprVariableRector::class,
			LongArrayToShortArrayRector::class,
		]
	)
	->withSkip([
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
	]);
