<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use DecodeLabs\Genesis\Context;

trait KernelTrait
{
    protected Context $context;
    protected bool $shutdown = false;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function shutdown(): void
    {
        if ($this->shutdown) {
            return;
        }

        $this->shutdown = true;
        $this->context->container['app']->shutdown();

        exit;
    }
}
