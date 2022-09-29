<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use df\Launchpad;

use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Kernel as KernelInterface;

class Kernel implements KernelInterface
{
    protected Context $context;
    protected bool $shutdown = false;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function run(): void
    {
        Launchpad::$app->startup($this->context->getStartTime());
        Launchpad::$app->run();
    }

    public function shutdown(): void
    {
        if ($this->shutdown) {
            return;
        }

        $this->shutdown = true;

        if (Launchpad::$app) {
            Launchpad::$app->shutdown();
        }

        if (Launchpad::$loader) {
            Launchpad::$loader->shutdown();
        }

        Launchpad::$app = null;
        Launchpad::$loader = null;

        exit;
    }
}
