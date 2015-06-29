<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;
    
class TaskPurgeBuilds extends arch\task\Action {

    const CONTINGENCY = 1;

    public function execute() {
        $contingency = (int)$this->request->query->get('contingency', self::CONTINGENCY);

        if($contingency < 0) {
            $contingency = 0;
        }

        $keepLast = true;
        $keepTesting = true;

        if(isset($this->request->query->all)) {
            $keepLast = false;
            $keepTesting = false;
            $contingency = 0;
        }

        $appPath = df\Launchpad::$applicationPath;
        $runDir = new core\fs\Dir($appPath.'/data/local/run');

        if(!$runDir->exists()) {
            $this->io->writeLine('No builds to purge');
            return;
        }

        $this->io->writeLine('Purging old builds...');
        $this->io->writeLine('Keeping '.$contingency.' build(s) as contingency');

        $list = scandir($runDir->getPath());
        sort($list);
        $testList = [];
        unset($list[0], $list[1]);

        foreach($list as $i => $entry) {
            if(substr($entry, -8) == '-testing') {
                unset($list[$i]);
                $testList[] = $entry;
            }
        }

        if(!isset($this->request->query->purgeTesting) && $keepTesting) {
            array_pop($testList);
        }

        if($keepLast) {
            $contingency++;
        }

        for($i = 0; $i < $contingency; $i++) {
            array_pop($list);
        }

        $list = array_merge($list, $testList);

        foreach($list as $entry) {
            $this->io->writeLine('Deleting build '.$entry);
            $runDir->getChild($entry)->unlink();
        }

        if($runDir->isEmpty()) {
            $this->io->writeLine('Deleting run folder');
            $runDir->unlink();
        }
    }
}