<?php

use DecodeLabs\Genesis;
use DecodeLabs\R7\Genesis\Bootstrap;
use DecodeLabs\R7\Genesis\Hub;

$parts = explode('/', str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME']))));

if (array_pop($parts) != 'entry') {
    throw new Exception(
        'Entry point does not appear to be valid'
    );
}

$appPath = implode('/', $parts);

// Load Genesis bootstrap
require_once $appPath.'/vendor/decodelabs/genesis/src/Bootstrap.php';
require_once __DIR__.'/provider/Genesis/Bootstrap.php';

// Run bootstrap
(new Bootstrap(__DIR__, $appPath))->run();

// Run app
$kernel = Genesis::initialize(Hub::class);
$kernel->run();
$kernel->shutdown();
