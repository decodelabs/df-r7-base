<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\aura;
use df\spur;

class TaskTest extends arch\task\Action {

    public function execute() {
        $this->io->write('Installing theme dependencies...');
        $this->io->writeLine();

        $installer = new spur\packaging\bower\Installer($this->io);
        $installer->installPackages(['jquery-simple-slider' => null]);
    }
}