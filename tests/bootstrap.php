<?php

require_once dirname(__DIR__).'/Df.php';

use df\core;
use df\apex;
use DecodeLabs\Glitch;

$startTime = df\Launchpad::initEnvironment();
$appDir = getcwd();
$hasAppFile = file_exists($appDir.'/App.php');

if (!$hasAppFile) {
    $appDir = dirname(__DIR__);
}

df\Launchpad::initLoaders($appDir, $startTime);
Glitch::setRunMode('development');
$appClass = 'df\\apex\\App';

if ($hasAppFile) {
    require_once $appDir.'/App.php';
} else {
    require_once __DIR__.'/App.php';
}

if (class_exists($appClass)) {
    df\Launchpad::$loader->loadPackages(array_keys($appClass::PACKAGES));
}
