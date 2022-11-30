<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\composer\_nodes;

use DecodeLabs\Atlas;

use DecodeLabs\Genesis;
use DecodeLabs\Integra;
use df\arch;

class TaskInstall extends arch\node\Task
{
    public function execute(): void
    {
        $file = Atlas::file(Genesis::$hub->getApplicationPath() . '/composer.json');

        if (!$file->exists()) {
            $this->runChild('composer/init?no-update');
        }

        $args = [];

        if (!Genesis::$environment->isDevelopment()) {
            $args[] = '--no-dev';
        }

        Integra::run('install', ...$args);
    }
}
