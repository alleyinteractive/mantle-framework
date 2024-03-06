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
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnExprInConstructRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\ConstFetch\RemovePhpVersionIdCheckRector;
use Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector;
use Rector\DeadCode\Rector\Expression\SimplifyMirrorAssignRector;
use Rector\DeadCode\Rector\For_\RemoveDeadContinueRector;
use Rector\DeadCode\Rector\For_\RemoveDeadIfForeachForRector;
use Rector\DeadCode\Rector\For_\RemoveDeadLoopRector;
use Rector\DeadCode\Rector\Foreach_\RemoveUnusedForeachKeyRector;
use Rector\DeadCode\Rector\FunctionLike\RemoveDeadReturnRector;
use Rector\DeadCode\Rector\If_\RemoveAlwaysTrueIfConditionRector;
use Rector\DeadCode\Rector\If_\RemoveDeadInstanceOfRector;
use Rector\DeadCode\Rector\If_\RemoveTypedPropertyDeadInstanceOfRector;
use Rector\DeadCode\Rector\If_\RemoveUnusedNonEmptyArrayBeforeForeachRector;
use Rector\DeadCode\Rector\If_\SimplifyIfElseWithSameContentRector;
use Rector\DeadCode\Rector\If_\UnwrapFutureCompatibleIfPhpVersionRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Plus\RemoveDeadZeroAndOneOperationRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\DeadCode\Rector\Switch_\RemoveDuplicatedCaseInSwitchRector;
use Rector\DeadCode\Rector\Ternary\TernaryToBooleanOrFalseToBooleanAndRector;
use Rector\DeadCode\Rector\TryCatch\RemoveDeadTryCatchRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeIfElseValueAssignToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\If_\RemoveAlwaysElseRector;
use Rector\EarlyReturn\Rector\Return_\PreparedValueToEarlyReturnRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\EarlyReturn\Rector\StmtsAwareInterface\ReturnEarlyIfVariableRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

/**
 * Rector Configuration
 *
 * Overtime this file will have more rules enabled for it. Right now, most of
 * them are commented out. They will be spread out over multiple pull requests.
 *
 * Rules that are known to cause issues and should not be used:
 *
 * - RenameForeachValueVariableToMatchMethodCallReturnTypeRector: causes variables to be converted to snakeCase
 */
return RectorConfig::configure()
	->withIndent( "\t" )
	->withPaths( [ __DIR__ . '/src' ] )
	// ->withPhpSets( php81: true )
	->withRules(
		[
			AddVoidReturnTypeWhereNoReturnRector::class,
			RemoveUselessReturnTagRector::class,
			RenameForeachValueVariableToMatchExprVariableRector::class,

			///////////////////////////////////////////////////
			// Dead Code Set
			///////////////////////////////////////////////////
			// easy picks
			RemoveUnusedForeachKeyRector::class,
			RemoveDuplicatedArrayKeyRector::class,
			RecastingRemovalRector::class,
			RemoveAndTrueRector::class,
			SimplifyMirrorAssignRector::class,
			RemoveDeadContinueRector::class,
			RemoveUnusedNonEmptyArrayBeforeForeachRector::class,
			RemoveNullPropertyInitializationRector::class,
			RemoveUselessReturnExprInConstructRector::class,
			RemoveTypedPropertyDeadInstanceOfRector::class,
			// TernaryToBooleanOrFalseToBooleanAndRector::class,
			// RemoveDoubleAssignRector::class,
			// RemoveConcatAutocastRector::class,
			// SimplifyIfElseWithSameContentRector::class,
			// SimplifyUselessVariableRector::class,
			// RemoveDeadZeroAndOneOperationRector::class,
			// docblock
			// RemoveUselessParamTagRector::class,
			// RemoveUselessReturnTagRector::class,
			// RemoveNonExistingVarAnnotationRector::class,
			// RemoveUselessVarTagRector::class,
			// RemovePhpVersionIdCheckRector::class,
			// RemoveAlwaysTrueIfConditionRector::class,
			// RemoveUnusedPrivateClassConstantRector::class,
			// RemoveUnusedPrivatePropertyRector::class,
			// RemoveDuplicatedCaseInSwitchRector::class,
			// RemoveDeadInstanceOfRector::class,
			// RemoveDeadTryCatchRector::class,
			// RemoveDeadIfForeachForRector::class,
			// RemoveDeadStmtRector::class,
			// UnwrapFutureCompatibleIfPhpVersionRector::class,
			// RemoveParentCallWithoutParentRector::class,
			// RemoveDeadConditionAboveReturnRector::class,
			// RemoveDeadLoopRector::class,
			// // removing methods could be risky if there is some magic loading them
			// RemoveUnusedPromotedPropertyRector::class,
			// RemoveUnusedPrivateMethodParameterRector::class,
			// RemoveUnusedPrivateMethodRector::class,
			// RemoveUnreachableStatementRector::class,
			// RemoveUnusedVariableAssignRector::class,
			// // this could break framework magic autowiring in some cases
			// RemoveUnusedConstructorParamRector::class,
			// RemoveEmptyClassMethodRector::class,
			// RemoveDeadReturnRector::class,

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
		]
	]);
