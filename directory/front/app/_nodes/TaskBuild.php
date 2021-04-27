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

use DecodeLabs\Terminus as Cli;

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
        Cli::newLine();


        // Setup controller
        $controller = new core\app\builder\Controller();

        if (isset($this->request['force'])) {
            $controller->shouldCompile(true);
        } elseif (isset($this->request['dev'])) {
            $controller->shouldCompile(false);
        }


        // Prepare info
        $buildId = $controller->getBuildId();

        if (!$controller->shouldCompile()) {
            Cli::info('Builder is running in dev mode, no build folder will be created');
        }

        // Creating build
        Cli::inlineInfo('Using build id: ');
        Cli::{'.brightMagenta'}($buildId);
        Cli::newLine();


        // Run custom tasks
        $this->runChild('./build-custom', false);


        // Clear config cache
        core\Config::clearLiveCache();


        if ($controller->shouldCompile()) {
            Cli::{'yellow'}('Packaging files:');


            // Create build
            foreach ($controller->createBuild() as $packageName) {
                Cli::{'brightMagenta'}(' '.$packageName);
            }

            Cli::newLine();
            Cli::newLine();

            // Late build tasks
            $this->runChild('./build-custom?after='.$buildId, false);

            // Copy current build
            Cli::{'yellow'}('Copying current build: ');
            $controller->copyCurrentBuild();
            Cli::success('done');

            Cli::{'yellow'}('Waiting for transition: ');

            Cli::newSpinner('brightBlue')
                ->waitFor(10)
                ->complete('done');


            // Move to run path
            Cli::{'yellow'}('Activating new build: ');
            $controller->activateBuild();
            Cli::success('done');

            // Deactivate previous
            Cli::{'yellow'}('Deactivating old build: ');
            $controller->deactivatePreviousBuild();
            Cli::success('done');

            Cli::newLine();
            Cli::newLine();
        } else {
            // Late build tasks
            $this->runChild('./build-custom?after', false);
        }


        // Generate entries
        $this->runChild('./generate-entry');
        Cli::newLine();

        // Clear cache
        $this->runChild('cache/purge');
        Cli::newLine();

        // Restart daemons
        $this->runChild('daemons/restart-all');
        Cli::newLine();

        // Purge
        $this->runChild('./purge-builds?active='.$buildId);
        Cli::newLine();

        // Task spool
        $this->runChild('tasks/scan');
        Cli::newLine();

        try {
            $this->runChild('tasks/spool');
        } catch (\Exception $e) {
            Cli::error($e->getMessage());
        }

        Cli::newLine();
    }
}
