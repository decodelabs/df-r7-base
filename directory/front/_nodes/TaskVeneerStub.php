<?php

/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\_nodes;

use DecodeLabs\Atlas;

use DecodeLabs\Terminus as Cli;
use DecodeLabs\Veneer\Stub\Generator;
use df\arch;

class TaskVeneerStub extends arch\node\Task
{
    public function execute(): void
    {
        $scanDir = Atlas::dir(getcwd());
        $stubDir = Atlas::dir('stubs/');

        // Scan
        $generator = new Generator($scanDir, $stubDir);
        $bindings = $generator->scan();

        if (empty($bindings)) {
            Cli::warning('There are no Veneer bindings to stub');
            exit;
        }

        Cli::newLine();

        foreach ($bindings as $binding) {
            Cli::{'brightMagenta'}($binding->getProviderClass() . ' ');
            $generator->generate($binding);
            Cli::success('done');
        }

        Cli::newLine();
    }
}
