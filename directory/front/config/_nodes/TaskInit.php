<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\config\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskInit extends arch\node\Task
{
    public function extractCliArguments(core\cli\ICommand $command)
    {
        foreach ($command->getArguments() as $arg) {
            if (!$arg->isOption()) {
                $this->request->query->environments[] = (string)$arg;
            }
        }
    }

    public function execute()
    {
        $this->ensureDfSource();

        if (!empty($this->request->query->environments)) {
            $currentEnv = null;
            
            foreach ($this->request->query->environments as $envNode) {
                core\Config::clearLiveCache();
                $currentEnv = df\Launchpad::$app->envId;
                df\Launchpad::$app->envId = $envNode->getValue();

                $this->_apply();
            }

            df\Launchpad::$app->envId = $currentEnv;
            core\Config::clearLiveCache();
        } else {
            $this->_apply();
        }
    }

    protected function _apply()
    {
        $this->io->write('Looking up config classes in:');
        $libList = df\Launchpad::$loader->lookupLibraryList();
        $classes = [];

        foreach ($libList as $libName) {
            $this->io->write(' '.$libName);
            $classes = array_merge($classes, $this->data->config->findIn($libName));
        }

        $classCount = count($classes);
        $this->io->writeLine();
        $this->io->write('Found '.$classCount);

        if (!$classCount) {
            $this->io->writeLine();
            return;
        }

        $this->io->write(':');

        foreach ($classes as $class => $isUnit) {
            if ($isUnit) {
                $id = implode('/', array_slice(explode('\\', $class), -3, -1));
                $config = axis\Model::loadUnitFromId($id);
            } else {
                $config = $class::getInstance();
            }

            $this->io->write(' '.ucfirst($config->getConfigId()));
        }

        $this->io->writeLine();
    }
}
