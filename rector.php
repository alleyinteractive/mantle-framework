<?php
/**
 * Rector Configuration
 *
 * phpcs:disable
 */

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\CodeQuality\Rector\If_\ObjectExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FunctionLike\FunctionLikeToFirstClassCallableRector;
use Rector\CodingStyle\Rector\If_\AlternativeIfToBracketRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveDuplicatedReturnSelfDocblockRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveMixedDocblockOverruledByNativeTypeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;

use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
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
			AddParamTypeDeclarationRector::class,
		]
	)
	->withSkip( [
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
			__DIR__ . '/src/Mantle/Support/Reflector.php',
		],
		ReturnNullableTypeRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Relations',
			__DIR__ . '/src/Mantle/Support/Reflector.php',
		],
		ReturnTypeFromStrictTypedCallRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Relations',
		],
		ArrayToFirstClassCallableRector::class,
		// Rector re-prints the nodes it edits with four tabs per indent level instead of
		// one, so any rule that rewrites a docblock or an if-block mangles the file.
		RemoveDuplicatedReturnSelfDocblockRector::class,
		RemoveMixedDocblockOverruledByNativeTypeRector::class,
		RemoveUselessUnionReturnDocblockRector::class,
		AlternativeIfToBracketRector::class,
		RemoveUselessParamTagRector::class,
		StrContainsRector::class,
		AddArrowFunctionReturnTypeRector::class,
		EmptyOnNullableObjectToInstanceOfRector::class,
		ExplicitNullableObjectInstanceRector::class,
		RemoveExtraParametersRector::class => [
			__DIR__ . '/src/Mantle/Support/helpers/helpers-general.php',
		],
		ReturnTypeFromReturnNewRector::class => [
			__DIR__ . '/src/Mantle/Support/Collection.php',
			__DIR__ . '/src/Mantle/Support/Traits/Enumerates_Values.php',
		],
		ReturnUnionTypeRector::class => [
			__DIR__ . '/src/Mantle/Framework/Exceptions/Handler.php',
			__DIR__ . '/src/Mantle/Support/Traits/Enumerates_Values.php',
		],
		ReturnTypeFromStrictFluentReturnRector::class => [
			__DIR__ . '/src/Mantle/Database/Query/Collection.php',
			__DIR__ . '/src/Mantle/Support/Collection.php',
			__DIR__ . '/src/Mantle/Support/Traits/Enumerates_Values.php',
		],
		ObjectExplicitBoolCompareRector::class => [
			__DIR__ . '/src/Mantle/Database/Model/Post.php',
			__DIR__ . '/src/Mantle/Testing',
		],
		SimplifyEmptyCheckOnEmptyArrayRector::class,
		CatchExceptionNameMatchingTypeRector::class,
		FlipTypeControlToUseExclusiveTypeRector::class,
		ClosureDelegatingCallToFirstClassCallableRector::class,
		ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
		RemoveNullArgOnNullDefaultParamRector::class,
		IfIssetToCoalescingRector::class,
		SafeDeclareStrictTypesRector::class,
	] );
