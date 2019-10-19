<?php

require_once __DIR__.'/Df.php';

use df\core;

// Load core library
df\Launchpad::loadBaseClass('core/_manifest');

// Register loader
if (df\Launchpad::$isCompiled) {
    df\Launchpad::$loader = new core\loader\Base(['root' => dirname(df\Launchpad::$rootPath)]);
} else {
    df\Launchpad::$loader = new core\loader\Development(['root' => dirname(df\Launchpad::$rootPath)]);
}


$appPath = __DIR__;

// Glitch
Glitch::registerPathAliases([
        'vendor' => $appPath.'/vendor',
        'root' => df\Launchpad::$isCompiled ? df\Launchpad::$rootPath : dirname(df\Launchpad::$rootPath)
    ])
    ->registerAsErrorHandler();

df\Launchpad::$loader->initRootPackages(df\Launchpad::$rootPath, $appPath);
Glitch::setRunMode('development');
