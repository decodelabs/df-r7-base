<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis;

use df\Launchpad;
use df\core\app\runner\Base as RunnerBase;
use df\user\Disciple\Adapter as DiscipleAdapter;

use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis\Context;
use DecodeLabs\Genesis\Kernel as KernelInterface;
use DecodeLabs\Metamorph;
use DecodeLabs\R7\Legacy;

use Throwable;

class Kernel implements KernelInterface
{
    protected Context $context;
    protected string $mode;
    protected bool $shutdown = false;

    public function __construct(
        Context $context,
        string $mode
    ) {
        $this->context = $context;
        $this->mode = $mode;
    }


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        // Set Disciple adapter
        Disciple::setAdapter(new DiscipleAdapter());


        // Set Metamorph URL resolver
        Metamorph::setUrlResolver(function (string $url): string {
            try {
                return (string)Legacy::uri($url);
            } catch (Throwable $e) {
                return $url;
            }
        });


        // Setup Veneer bindings
        $app = $this->context->container['app'];
        $app->setupVeneerBindings();
    }


    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return $this->mode;
    }



    public function run(): void
    {
        // Load runner
        $runner = RunnerBase::factory($this->getMode());

        // Add runner to container
        $this->context->container->bindShared(RunnerBase::class, $runner)
            ->alias('app.runner');

        // DELETE ME
        Launchpad::$runner = $runner;

        // Dispatch runner
        $runner->dispatch();
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

        exit;
    }
}
