<?php

use DecodeLabs\Genesis;
use DecodeLabs\R7\Genesis\Bootstrap;
use DecodeLabs\R7\Genesis\Hub;

// Load Genesis bootstrap
require_once __DIR__.'/provider/Genesis/Bootstrap.php';

// Run bootstrap
(new Bootstrap())->run();

// Run app
$kernel = Genesis::initialize(Hub::class) ?? Genesis::$kernel;
$kernel->run();
$kernel->shutdown();
