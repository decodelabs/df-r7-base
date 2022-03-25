<?php

require_once dirname(__DIR__).'/Df.php';

use df\core;
use df\apex;
use DecodeLabs\Glitch;
use DecodeLabs\Veneer;

$startTime = df\Launchpad::initEnvironment();
Veneer::getDefaultManager()->setDeferrals(false);
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
    $appClass::setupVeneerBindings();
}

error_reporting(false);
