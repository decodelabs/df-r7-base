<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\app\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;
use df\flex;

class TaskBuild extends arch\node\Task
{
    public function extractCliArguments(core\cli\ICommand $command)
    {
        $inspector = new core\cli\Inspector([
            'dev|development|d' => 'Do not compile',
            'force|f' => 'Force compilation'
        ], $command);

        if ($inspector['force']) {
            $this->request->query->force = true;
        }

        if ($inspector['dev']) {
            $this->request->query->dev = true;
        }
    }

    public function execute()
    {
        $this->ensureDfSource();


        // Setup controller
        $controller = new core\app\builder\Controller();
        $controller->setMultiplexer($this->io);

        if (isset($this->request['force'])) {
            $controller->shouldCompile(true);
        } elseif (isset($this->request['dev'])) {
            $controller->shouldCompile(false);
        }


        // Prepare info
        $buildId = $controller->getBuildId();

        if (!$controller->shouldCompile()) {
            $this->io->writeLine('Builder is running in dev mode, no build folder will be created');
            $this->io->writeLine();
        }

        // Creating build
        $this->io->writeLine('Using build id: '.$buildId);
        $this->io->writeLine();


        // Run custom tasks
        $this->runChild('./build-custom', false);


        // Clear config cache
        core\Config::clearLiveCache();


        if ($controller->shouldCompile()) {
            $this->io->write('Packaging files: ');


            // Create build
            foreach ($controller->createBuild() as $packageName) {
                $this->io->write(' '.$packageName);
            }

            $this->io->writeLine();

            // Late build tasks
            $this->runChild('./build-custom?after='.$buildId, false);

            // Move to run path
            $controller->activateBuild();
        } else {
            // Late build tasks
            $this->runChild('./build-custom?after', false);
        }


        // Generate entries
        $this->runChild('./generate-entry', false);

        // Clear cache
        $this->io->writeLine();
        $this->io->writeLine('Purging cache backends...');
        $this->runChild('cache/purge');

        // Restart daemons
        $this->io->writeLine();
        $this->runChild('daemons/restart-all', false);

        // Purge
        $this->io->writeLine();
        $this->io->outdent();
        $this->runChild('./purge-builds?active='.$buildId);
        $this->io->indent();

        // Task spool
        $this->io->writeLine();
        $this->io->writeLine('Running task spool...');
        $this->runChild('tasks/spool');
    }
}
