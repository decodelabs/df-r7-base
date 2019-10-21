<?php

require_once __DIR__.'/Df.php';

use df\core;
use DecodeLabs\Glitch;

$appPath = __DIR__;
$startTime = df\Launchpad::initEnvironment($appPath);
df\Launchpad::initLoaders($appPath, $startTime);
Glitch::setRunMode('development');
