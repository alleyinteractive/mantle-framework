<?php
/**
 * Rector Configuration
 *
 * phpcs:disable
 */

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
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
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
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
	->withPhpVersion( PhpVersion::PHP_83 )
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
		// Adding #[\Override] shifts PHPStan's reported line for this file's
		// intentionally-suppressed generics false-positives, breaking the inline
		// @phpstan-ignore-line directives on the overridden methods.
		AddOverrideAttributeToOverriddenMethodsRector::class => [
			__DIR__ . '/src/Mantle/Database/Query/Collection.php',
		],
		AddVoidReturnTypeWhereNoReturnRector::class => [
			__DIR__ . '/src/Mantle/Testing/Concerns/Core_Shim.php',
			__DIR__ . '/tests/Testing/CoreTestShimTest.php',
			__DIR__ . '/tests/testing/CoreTestShimTest.php',
		],
		ClassPropertyAssignToConstructorPromotionRector::class => [
			__DIR__ . '/src/Mantle/Support/Service_Provider.php',
		],
		RemoveUselessReturnTagRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Relations',
		],
		ReturnNullableTypeRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Relations',
		],
		ReturnTypeFromStrictTypedCallRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Relations',
		],
		ArrayToFirstClassCallableRector::class,
		RemoveUselessParamTagRector::class,
		StrContainsRector::class,
		AddArrowFunctionReturnTypeRector::class,
		ChangeOrIfContinueToMultiContinueRector::class,
		EmptyOnNullableObjectToInstanceOfRector::class,
		ReturnBinaryOrToEarlyReturnRector::class => [
			__DIR__ . '/src/Mantle/Http_Client/Response.php',
		],
		RemoveExtraParametersRector::class => [
			__DIR__ . '/src/Mantle/Support/helpers/helpers-general.php',
		],
		ReturnTypeFromReturnNewRector::class => [
			__DIR__ . '/src/Mantle/Support/Collection.php',
			__DIR__ . '/src/Mantle/Support/Traits/Enumerates_Values.php',
		],
		ReturnUnionTypeRector::class => [
			__DIR__ . '/src/Mantle/Framework/Exceptions/Handler.php',
		],
		ReturnTypeFromStrictFluentReturnRector::class => [
			__DIR__ . '/src/Mantle/Database/Query/Collection.php',
			__DIR__ . '/src/Mantle/Support/Collection.php',
			__DIR__ . '/src/Mantle/Support/Traits/Enumerates_Values.php',
		],
		ExplicitBoolCompareRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Post.php',
			__DIR__ . '/src/Mantle/Testing',
		],
		SimplifyEmptyCheckOnEmptyArrayRector::class,
		DisallowedEmptyRuleFixerRector::class,
		NullableCompareToNullRector::class,
		CatchExceptionNameMatchingTypeRector::class,
		EncapsedStringsToSprintfRector::class,
		FlipTypeControlToUseExclusiveTypeRector::class,
		ClosureDelegatingCallToFirstClassCallableRector::class,
		ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
		RemoveNullArgOnNullDefaultParamRector::class,
		IfIssetToCoalescingRector::class,
		SafeDeclareStrictTypesRector::class,
	] );
