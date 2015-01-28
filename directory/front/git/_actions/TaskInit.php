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

        $this->io->writeLine('Turning off file mode');
        $repo->setConfig('core.filemode', false);

        $this->io->write('Would you like to set default GUI config @1020p? [N/y] ');
        $answer = trim($this->io->readLine());

        if($this->format->stringToBoolean($answer, false)) {
            $geometry = '1914x1036+5+23 450 300';
            $this->io->writeLine('Setting geometry to: '.$geometry);

            $repo->setConfig('gui.wmstate', 'zoomed');
            $repo->setConfig('gui.geometry', $geometry);
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
            }

            $repo->addRemote('origin', $origin);
        }

        if($push) {
            $this->io->writeLine('Pushing initial commit');
            $repo->pushUpstream();
        }

        $this->io->writeLine('Done');
    }
}