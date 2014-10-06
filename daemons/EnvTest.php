<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\daemons;

use df;
use df\core;
use df\apex;
use df\halo;

class EnvTest extends halo\daemon\Base {
    
    const TEST_MODE = true;

    protected function _setup() {
        $this->events->bindTimerOnce('test', 0.5, [$this, 'test']);
    }

    public function test() {
        if(extension_loaded('pcntl')) {
            $this->io->writeLine('Pcntl is enabled');
        } else {
            $this->io->writeErrorLine('!! Pcntl is disabled');
        }

        if(extension_loaded('posix')) {
            $this->io->writeLine('Posix is enabled');
        } else {
            $this->io->writeErrorLine('!! Posix is disabled');
        }

        $this->io->writeLine();

        $remote = halo\daemon\Remote::factory($this);

        if($remote->isRunning()) {
            $this->io->writeLine('The remote could correctly detect this daemon is running');
        } else {
            $this->io->writeErrorLine('!! The remote could not detect this daemon');
        }

        $this->stop();
    }
}