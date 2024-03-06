<?php
/**
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
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchMethodCallReturnTypeRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;

/**
 * Rector Configuration
 *
 * Overtime this file will have more rules enabled for it. Right now, most of
 * them are commented out. They will be spread out over multiple pull requests.
 *
 * Rules that are known to cause issues and should not be used:
 *
 * - RenameForeachValueVariableToMatchMethodCallReturnTypeRector: causes variables to be converted to snakeCase
 * - RemoveUselessParamTagRector: Conflicts with WordPress Coding Standards
 * - MixedTypeRector: Conflicts with WordPress Coding Standards
 */
return RectorConfig::configure()
	->withIndent( "\t" )
	->withPaths( [ __DIR__ . '/src' ] )
	->withPreparedSets(
		deadCode: true,
	)
	->withTypeCoverageLevel(2)
	->withPhpSets( php81: true )
	->withRules(
		[
			AddVoidReturnTypeWhereNoReturnRector::class,
			RenameForeachValueVariableToMatchExprVariableRector::class,

			// Early Return
			// ChangeNestedForeachIfsToEarlyContinueRector::class,
			// ChangeAndIfToEarlyReturnRector::class,
			// ChangeIfElseValueAssignToEarlyReturnRector::class,
			// ChangeNestedIfsToEarlyReturnRector::class,
			// RemoveAlwaysElseRector::class,
			// ChangeOrIfContinueToMultiContinueRector::class,
			// PreparedValueToEarlyReturnRector::class,
			// ReturnBinaryOrToEarlyReturnRector::class,
			// ReturnEarlyIfVariableRector::class,
		]
	)
	->withSkip([
		AddVoidReturnTypeWhereNoReturnRector::class => [
			__DIR__ . '/src/Mantle/testing/concerns/trait-core-shim.php',
			__DIR__ . '/tests/Testing/CoreTestShimTest.php',
		],
		RemoveUselessParamTagRector::class,
		MixedTypeRector::class,
		RenameForeachValueVariableToMatchMethodCallReturnTypeRector::class,
		FirstClassCallableRector::class,
		StrContainsRector::class,
	]);
