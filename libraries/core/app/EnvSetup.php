<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app;

use df\core;
use df\Launchpad;

use DecodeLabs\Veneer;
use DecodeLabs\Glitch;

class EnvSetup
{
    public static function setup(string $appPath, float $startTime=null)
    {
        // Veneer
        Veneer::blacklistNamespaces('df')
            ->whitelistNamespaces('df\\apex\\directory');

        // Glitch
        $glitch = Glitch::setStartTime($startTime ?? microtime(true))
            ->registerPathAliases([
                'vendor' => $appPath.'/vendor',
                'root' => Launchpad::$isCompiled ? Launchpad::$rootPath : dirname(Launchpad::$rootPath)
            ])
            ->registerAsErrorHandler()
            ->setLogListener(function ($exception) {
                core\logException($exception);
            });
    }
}
