<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\test\_nodes;

use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\flex;

class TaskDependencies extends arch\node\Task
{
    public function execute(): void
    {
        $this->ensureDfSource();

        $scanner = new flex\code\Scanner(null, [
            new flex\code\probe\Dependencies()
        ]);

        $scanner->addFrameworkPackageLocations(true);
        Legacy::getLoader()->loadPackages(array_keys($scanner->getLocations()));

        Cli::{'yellow'}('Scanning packages:');
        $errors = [];

        foreach ($scanner->locations as $location) {
            Cli::{'brightMagenta'}(' ' . $location->id);
            $errors = array_merge($errors, $location->scan($scanner)['dependencies']->getErrors());
        }

        Cli::newLine();

        if (empty($errors)) {
            Cli::success('Happy days, no errors detected!');
        } else {
            Cli::newLine();

            foreach ($errors as $path => $error) {
                Cli::error($error);
            }
        }
    }
}
