<?php

/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\_nodes;

use df\arch;

use DecodeLabs\Atlas;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer;
use DecodeLabs\Veneer\Stub\Generator;

class TaskVeneerStub extends arch\node\Task
{
    public function execute()
    {
        // Get bindings
        $manager = Veneer::getDefaultManager();
        $bindings = $manager->getBindings();

        // Prepare arguments
        Cli::getCommandDefinition()
            ->addArgument('task', 'Task entry')
            ->addArgument('?binding', 'Name of the binding');
        Cli::prepareArguments();

        // Ask for binding name
        Cli::newLine();
        foreach ($bindings as $proxyClass => $binding) {
            Cli::{'>.yellow'}($proxyClass);
        }
        Cli::newLine();

        if (null === ($bindingName = Cli::getArgument('binding'))) {
            $bindingName = Cli::newQuestion('Which binding do you want to stub?')
                //->setOptions(...array_keys($bindings))
                ->prompt();
        }

        $bindingName = ucfirst($bindingName);

        // Load binding
        if (!isset($bindings[$bindingName])) {
            Cli::operative('Binding not found');
            exit;
        }

        $binding = $bindings[$bindingName];


        // Generate
        $scanDir = Atlas::dir($this->app->getPath());
        $stubDir = Atlas::createDir($this->app->getPath() . '/stubs');
        $generator = new Generator($scanDir, $stubDir);
        $generator->generate($binding);

        Cli::success('done');
    }
}
