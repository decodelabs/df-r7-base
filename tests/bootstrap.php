<?php

require_once dirname(__DIR__).'/Df.php';

use df\core;
use DecodeLabs\Glitch;

$startTime = df\Launchpad::initEnvironment();
df\Launchpad::initLoaders(__DIR__, $startTime);
Glitch::setRunMode('development');

df\Launchpad::$loader->loadPackages([
    'nightfire', 'touchstone', 'webCore'
]);
