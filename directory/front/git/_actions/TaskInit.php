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

    protected function _run() {
        $path = df\Launchpad::$applicationPath;
        $this->runChild('git/init-gitignore');

        if(is_dir($path.'/.git')) {
            $this->response->writeErrorLine('App repository has already been initialized');
            $repo = new spur\vcs\git\Repository($path);
        } else {
            $this->response->writeLine('Initialising git repository');
            $repo = spur\vcs\git\Repository::createNew($path);
        }

        $push = false;

        if(!$repo->countCommits()) {
            $this->response->writeLine('Making initial commit');
            $repo->commitAllChanges('Initial commit');
            $push = true;
        }

        if(!$repo->countRemotes()) {
            $this->response->write('>> Please enter remote origin: ');
            $origin = trim($this->response->readLine());

            if(!preg_match('/^(http(s)?|git\@)/i', $origin)) {
                $this->response->writeErrorLine('This doesn\'t look like a valid remote url');
            }

            $repo->addRemote('origin', $origin);
        }

        if($push) {
            $this->response->writeLine('Pushing initial commit');
            $repo->pushUpstream();
        }

        $this->response->writeLine('Done');
    }
}