<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\df\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskSetup extends arch\node\Task {

    public function execute() {
        $fileName = basename($_SERVER['PHP_SELF']);

        if($fileName !== 'Setup.php') {
            throw core\Error::ESetup('This task can only be run by the Setup.php script');
        }

        $this->io->writeLine('Setting up Decode Framework...');

        // Install git repos
        $this->runChild('git/install?all');


        // Permissions
        $this->io->writeLine();
        $this->io->writeLine('Setting permissions...');

        $config = core\environment\Config::getInstance();
        $user = $config->getDaemonUser();
        $group = $config->getDaemonGroup();

        $user = $this->_askFor('www user', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('user', 'text');
        }, $user, true);

        $group = $this->_askFor('www group', function($answer) {
            return $this->data->newValidator()
                ->addRequiredField('group', 'text');
        }, $group, true);

        $path = dirname(df\Launchpad::DF_PATH);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            dirname(df\Launchpad::DF_PATH)
        ));

        foreach($iterator as $item) {
            chmod($item, 0770);
            chown($item, $user);
            chgrp($item, $group);
        }

        // Clean up
        core\fs\Dir::delete(df\Launchpad::DF_PATH.'/setup');
        $this->io->writeLine('All done :)');
    }
}
