<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use df\core\app\runner\Base as RunnerBase;

use DecodeLabs\Genesis\Context;

/**
 * @template T of RunnerBase
 */
trait KernelTrait
{
    protected Context $context;
    protected bool $shutdown = false;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @phpstan-return T
     */
    protected function loadRunner(): RunnerBase
    {
        // Load runner
        /** @phpstan-var T */
        $runner = RunnerBase::factory($this->getMode());

        // Add runner to container
        $this->context->container->bindShared(RunnerBase::class, $runner)
            ->alias('app.runner');

        return $runner;
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
