<?php

// PHPUnit environment variables must be read instead of Laravel's local config cache.
$configCache = __DIR__.'/../bootstrap/cache/config.php';

if (file_exists($configCache) && ! unlink($configCache)) {
    throw new RuntimeException('Could not clear Laravel config cache for tests.');
}

require __DIR__.'/../vendor/autoload.php';
