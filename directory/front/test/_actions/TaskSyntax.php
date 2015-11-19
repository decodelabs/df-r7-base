<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\test\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\flex;

class TaskSyntax extends arch\action\Task {

    public function execute() {
        $scanner = new flex\code\Scanner(null, [
            new flex\code\probe\Syntax()
        ]);

        $scanner->addFrameworkPackageLocations();
        $this->io->write('Scanning packages:');
        $errors = [];

        foreach($scanner->locations as $location) {
            $this->io->write(' '.$location->id);
            $errors = array_merge($errors, $location->scan($scanner)['syntax']->getErrors());
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