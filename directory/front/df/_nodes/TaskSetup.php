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
use df\halo;

class TaskSetup extends arch\node\Task {

    public function execute() {
        $fileName = basename($_SERVER['PHP_SELF']);

        if($fileName !== 'Setup.php') {
            throw core\Error::ESetup('This task can only be run by the Setup.php script');
        }

        $this->io->writeLine('Setting up Decode Framework...');

        // Install git repos
        $this->runChild('git/install?all');
        $this->runChild('git/update?all');


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
        $this->io->writeLine();

        $this->io->writeLine('sudo chmod -R 0770 '.$path);
        $this->io->writeLine('sudo chmod -R '.$user.':'.$group.' '.$path);

        halo\process\Base::launch('sudo chmod', [
            '-R', '0770', $path
        ], null, $this->io);


        halo\process\Base::launch('sudo chown', [
            '-R', $user.':'.$group, $path
        ], null, $this->io);


        // Clean up
        core\fs\Dir::delete(df\Launchpad::DF_PATH.'/setup');
        $this->io->writeLine('All done :)');
    }
}
