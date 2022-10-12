<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use df\core\app\runner\Http as HttpRunner;

use DecodeLabs\Genesis\Kernel;
use DecodeLabs\R7\Genesis\KernelTrait;

class Http implements Kernel
{
    /**
     * @use KernelTrait<HttpRunner>
     */
    use KernelTrait;

    protected HttpRunner $runner;


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        $this->runner = $this->loadRunner();
    }

    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return 'Http';
    }

    /**
     * Run app
     */
    public function run(): void
    {
        // Dispatch runner
        $this->runner->dispatch();
    }
}
