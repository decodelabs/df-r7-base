<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df;

use df;
use df\core;

use DecodeLabs\Genesis;
use DecodeLabs\R7\Genesis\Hub;

class Launchpad
{
    public static bool $isCompiled = false;
    public static ?int $compileTimestamp = null;

    public static string $rootPath = __DIR__;

    public static $loader;
    public static $app;
    public static $runner;


    public static function run(): void
    {
        Genesis::run(Hub::class);
    }
}
