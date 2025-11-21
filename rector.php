<?php
/**
 * Rector Configuration
 *
 * phpcs:disable
 */

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\FunctionLike\FunctionLikeToFirstClassCallableRector;
use Rector\CodingStyle\Rector\If_\NullableCompareToNullRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;

use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\Return_\ReturnBinaryOrToEarlyReturnRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnArrayDocblockBasedOnArrayMapRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
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
		deadCode: true,
		codingStyle: true,
		codeQuality: true,
		earlyReturn: true,
		instanceOf: true,
		typeDeclarations: true,
	)
	->withRules(
		[
			RenameForeachValueVariableToMatchExprVariableRector::class,
			ExplicitNullableParamTypeRector::class,
			AddParamTypeDeclarationRector::class,
			AddReturnArrayDocblockBasedOnArrayMapRector::class,
		]
	)
	->withSkip( [
		AddVoidReturnTypeWhereNoReturnRector::class => [
			__DIR__ . '/src/mantle/testing/concerns/trait-core-shim.php',
			__DIR__ . '/tests/Testing/CoreTestShimTest.php',
			__DIR__ . '/tests/testing/CoreTestShimTest.php',
		],
		ClassPropertyAssignToConstructorPromotionRector::class => [
			__DIR__ . '/src/mantle/support/class-service-provider.php',
		],
		RemoveUselessReturnTagRector::class => [
			__DIR__ . '/src/mantle/database/model/relations',
		],
		ReturnNullableTypeRector::class => [
			__DIR__ . '/src/mantle/database/model/relations',
		],
		ReturnTypeFromStrictTypedCallRector::class => [
			__DIR__ . '/src/mantle/database/model/relations',
		],
		FirstClassCallableRector::class,
		RemoveUselessParamTagRector::class,
		StrContainsRector::class,
		AddArrowFunctionReturnTypeRector::class,
		ChangeOrIfContinueToMultiContinueRector::class,
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
		ReturnUnionTypeRector::class => [
			__DIR__ . '/src/mantle/framework/exceptions/class-handler.php',
		],
		ReturnTypeFromStrictFluentReturnRector::class => [
			__DIR__ . '/src/mantle/database/query/class-collection.php',
			__DIR__ . '/src/mantle/support/class-collection.php',
			__DIR__ . '/src/mantle/support/traits/trait-enumerates-values.php',
		],
		ExplicitBoolCompareRector::class => [
			__DIR__ . '/src/mantle/database/model/class-post.php',
			__DIR__ . '/src/mantle/testing',
		],
		SimplifyEmptyCheckOnEmptyArrayRector::class,
		DisallowedEmptyRuleFixerRector::class,
		NullableCompareToNullRector::class,
		CatchExceptionNameMatchingTypeRector::class,
		EncapsedStringsToSprintfRector::class,
		FlipTypeControlToUseExclusiveTypeRector::class,
		FunctionLikeToFirstClassCallableRector::class,
		RemoveNullArgOnNullDefaultParamRector::class,
		IfIssetToCoalescingRector::class,
	] );
