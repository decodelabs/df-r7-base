<?php

use DecodeLabs\Genesis;
use DecodeLabs\R7\Genesis\Bootstrap;
use DecodeLabs\R7\Genesis\Hub;

$entryPath = dirname(realpath($_SERVER['SCRIPT_FILENAME']));

if (!str_ends_with($entryPath, '/entry')) {
    throw new Exception(
        'Entry point does not appear to be valid'
    );
}

// Load Genesis bootstrap
require_once __DIR__.'/provider/Genesis/Bootstrap.php';

// Run bootstrap
(new Bootstrap(__DIR__, dirname($entryPath)))->run();

// Run app
$kernel = Genesis::initialize(Hub::class) ?? Genesis::$kernel;
$kernel->run();
$kernel->shutdown();
