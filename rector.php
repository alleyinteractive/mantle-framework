<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        // __DIR__ . '/tests',
    ])
		->withRules([
			AddVoidReturnTypeWhereNoReturnRector::class,
		]);
