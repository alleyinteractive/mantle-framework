<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        // __DIR__ . '/tests',
    ])
    ->withPreparedSets(deadCode: true);
