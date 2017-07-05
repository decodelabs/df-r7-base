<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\test\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\flex;

class TaskDependencies extends arch\node\Task {

    public function execute() {
        $scanner = new flex\code\Scanner(null, [
            new flex\code\probe\Dependencies()
        ]);

        $scanner->addFrameworkPackageLocations(true);
        df\Launchpad::$loader->loadPackages(array_keys($scanner->getLocations()));

        $this->io->write('Scanning packages:');
        $errors = [];

        foreach($scanner->locations as $location) {
            $this->io->write(' '.$location->id);
            $errors = array_merge($errors, $location->scan($scanner)['dependencies']->getErrors());
        }

        $this->io->writeLine();

        if(empty($errors)) {
            $this->io->writeLine('Happy days, no errors detected!');
        } else {
            $this->io->writeLine();

            foreach($errors as $path => $error) {
                $this->io->writeLine($error);
            }
        }
    }
}
