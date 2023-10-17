<?php

$parts = explode('/', str_replace('\\', '/', dirname(realpath($_SERVER['SCRIPT_FILENAME']))));

if (array_pop($parts) != 'entry') {
    throw new Exception(
        'Entry point does not appear to be valid'
    );
}

$appPath = implode('/', $parts);

// Load Genesis bootstrap
require_once $appPath.'/vendor/decodelabs/genesis/src/bootstrap.php';
require_once __DIR__.'/provider/Genesis/Bootstrap.php';

(new DecodeLabs\R7\Genesis\Bootstrap(__DIR__, $appPath))->run();
