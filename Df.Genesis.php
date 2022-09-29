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

require_once __DIR__.'/Df.Base.php';

class Launchpad extends LaunchpadBase
{
    public static function run(): void
    {
        Genesis::run(Hub::class);
    }

    public static function initEnvironment(): float
    {
        Genesis::initialize(Hub::class);
        return Genesis::getStartTime();
    }
}
