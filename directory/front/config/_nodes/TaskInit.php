<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\config\_nodes;

use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\axis;

class TaskInit extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();

        Cli::{'yellow'}('Looking up configs:');
        $libList = Legacy::getLoader()->lookupLibraryList();
        $classes = [];

        foreach ($libList as $libName) {
            Cli::{'brightMagenta'}(' ' . $libName);
            $classes = array_merge($classes, $this->data->config->findIn($libName));
        }

        $classCount = count($classes);
        Cli::newLine();

        Cli::inlineSuccess('Found ' . $classCount);

        if (!$classCount) {
            Cli::newLine();
            return;
        }

        Cli::write(':');

        foreach ($classes as $class => $isUnit) {
            if ($isUnit) {
                $id = implode('/', array_slice(explode('\\', $class), -3, -1));
                $config = axis\Model::loadUnitFromId($id);
            } elseif (class_exists($class) && is_subclass_of($class, 'df\\core\\Config')) {
                $config = $class::getInstance();
            } else {
                continue;
            }

            Cli::{'green'}(' ' . ucfirst($config->getConfigId()));
        }

        Cli::newLine();
    }
}
