<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\git\_actions;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
use df\spur;
    
class TaskInit extends arch\task\Action {

    const GEOMETRY = '1914x1036+5+23 450 300';

    public function execute() {
        $path = df\Launchpad::$applicationPath;
        $this->runChild('git/init-gitignore');

        if(is_dir($path.'/.git')) {
            $this->io->writeErrorLine('App repository has already been initialized');
            $repo = new spur\vcs\git\Repository($path);
        } else {
            $this->io->writeLine('Initialising git repository');
            $repo = spur\vcs\git\Repository::createNew($path);
        }

        if($repo->getConfig('core.filemode')) {
            $this->io->writeLine('Turning off file mode');
            $repo->setConfig('core.filemode', false);
        }

        if($repo->getConfig('gui.geometry') != self::GEOMETRY) {
            if($this->_askBoolean('Would you like to set default GUI config @1020p?', true)) {
                $this->io->writeLine('Setting geometry to: '.self::GEOMETRY);

                $repo->setConfig('gui.wmstate', 'zoomed');
                $repo->setConfig('gui.geometry', self::GEOMETRY);
            }        
        }

        $push = false;

        if(!$repo->countCommits()) {
            $this->io->writeLine('Making initial commit');
            $repo->commitAllChanges('Initial commit');
            $push = true;
        }

        if(!$repo->countRemotes()) {
            $this->io->write('>> Please enter remote origin: ');
            $origin = trim($this->io->readLine());

            if(!preg_match('/^(http(s)?|git\@)/i', $origin)) {
                $this->io->writeErrorLine('This doesn\'t look like a valid remote url');
                $push = false;
            } else {
                $repo->addRemote('origin', $origin);
            }
        }

        if($push) {
            $this->io->writeLine('Pushing initial commit');
            $repo->pushUpstream();
        }
    }
}