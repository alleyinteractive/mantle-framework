<?php
/**
 * Rector Configuration
 *
 * phpcs:disable
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\If_\AlternativeIfToBracketRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveMixedDocblockOverruledByNativeTypeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use Rector\Php70\Rector\StmtsAwareInterface\IfIssetToCoalescingRector;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassLike\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNullableTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictFluentReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Rector\ValueObject\PhpVersion;

/**
 * Rules in `withSkip()` are disabled on purpose.
 *
 * - RemoveUselessParamTagRector: Conflicts with WordPress Coding Standards.
 * - Docblock rules (RemoveMixedDocblockOverruledByNativeTypeRector, RemoveUselessUnionReturnDocblockRector AlternativeIfToBracketRector): Rector re-prints edited nodes with extra tabs and mangles the file.
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
		RemoveMixedDocblockOverruledByNativeTypeRector::class,
		RemoveUselessUnionReturnDocblockRector::class,
		AlternativeIfToBracketRector::class,
		RemoveUselessParamTagRector::class,
		StrContainsRector::class,
		AddArrowFunctionReturnTypeRector::class,
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
		SimplifyEmptyCheckOnEmptyArrayRector::class,
		CatchExceptionNameMatchingTypeRector::class,
		ClosureDelegatingCallToFirstClassCallableRector::class,
		ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
		RemoveNullArgOnNullDefaultParamRector::class,
		IfIssetToCoalescingRector::class,
		SafeDeclareStrictTypesRector::class,
	] );
