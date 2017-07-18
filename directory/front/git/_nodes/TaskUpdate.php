<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
use df\spur;

class TaskUpdate extends arch\node\Task {

    public function extractCliArguments(core\cli\ICommand $command) {
        foreach($command->getArguments() as $arg) {
            if(!$arg->isOption()) {
                $this->request->query->packages[] = (string)$arg;
            }
        }
    }

    public function execute() {
        $this->ensureDfSource();

        $names = $this->request->query->packages->toArray();

        if($this->request->query->has('package')) {
            $names[] = $this->request['package'];
        }

        if(empty($names)) {
            $this->runChild('git/update-all', false);
            return;
        }

        foreach($names as $name) {
            $this->io->writeLine('Pulling updates for package "'.$name.'"');
            $model = $this->data->getModel('package');

            try {
                if(!$result = $model->pull($name)) {
                    $this->io->writeLine('!! Package "'.$name.'" repo could not be found !!');
                } else {
                    $this->io->writeLine($result);
                }

                $this->io->writeLine();
            } catch(spur\vcs\git\IException $e) {
                $this->io->writeErrorLine($e->getMessage());
                return;
            }
        }

        if($this->app->isDevelopment()) {
            $this->runChild('application/build?dev', false);
        } else if($this->app->isTesting()) {
            $this->runChild('application/build', false);
        }
    }
}
