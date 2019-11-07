<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
use df\spur;

use DecodeLabs\Terminus\Cli;

class TaskComposer extends arch\node\Task
{
    public function execute()
    {
        $args = $_SERVER['argv'];
        array_shift($args);
        array_shift($args);

        $next = $args[0] ?? null;

        switch ($next) {
            case 'install':
                if (!$this->app->isDevelopment()) {
                    $args[] = '--no-dev';
                }
                break;
        }

        $file = Atlas::$fs->file($this->app->path.'/composer.json');

        if (!$file->exists()) {
            $this->runChild('composer/init?no-update');
        }

        Systemic::$process->newLauncher('composer', $args)
            ->setWorkingDirectory($this->app->path)
            ->setBroker(Cli::getSession()->getBroker())
            ->launch();
    }
}
