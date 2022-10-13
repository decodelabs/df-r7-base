<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use df\core\app\runner\Base as RunnerBase;
use df\core\app\runner\Http as HttpRunner;

use DecodeLabs\Genesis\Kernel;
use DecodeLabs\R7\Genesis\KernelTrait;

class Http implements Kernel
{
    use KernelTrait;

    protected HttpRunner $runner;


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        // Load runner
        /** @var HttpRunner $runner */
        $runner = RunnerBase::factory('Http');
        $this->runner = $runner;

        // Add runner to container
        $this->context->container->bindShared(RunnerBase::class, $this->runner)
            ->alias('app.runner');



        // Http env
        $this->initializeHttpEnv();
    }


    protected function initializeHttpEnv(): void
    {
        // If you're on apache, it sometimes hides some env variables = v. annoying
        if (
            function_exists('apache_request_headers') &&
            false !== ($apache = apache_request_headers())
        ) {
            foreach ($apache as $key => $value) {
                $_SERVER['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $_SERVER['HTTP_CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];
        }

        // Normalize REQUEST_URI
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
        }


        // Normalize Cloudflare proxy
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
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
