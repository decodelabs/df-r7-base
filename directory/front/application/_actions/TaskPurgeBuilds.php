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

    protected function _run() {
        $contingency = (int)$this->request->query->get('contingency', self::CONTINGENCY);

        if($contingency < 0) {
            $contingency = 0;
        }

        $appPath = df\Launchpad::$applicationPath;
        $runPath = $appPath.'/data/local/run';

        $this->response->writeLine('Purging old builds...');
        $this->response->writeLine('Keeping '.$contingency.' build(s) as contingency');

        $list = scandir($runPath);
        sort($list);
        unset($list[0], $list[1]);

        for($i = 0; $i < $contingency + 1; $i++) {
            array_pop($list);
        }

        foreach($list as $entry) {
            if(is_file($runPath.'/'.$entry)) {
                $this->response->writeLine('Deleting file build '.$entry);

                core\io\Util::deleteFile($runPath.'/'.$entry);
            } else if(is_dir($runPath.'/'.$entry)) {
                $this->response->writeLine('Deleting build '.$entry);

                core\io\Util::deleteDir($runPath.'/'.$entry);
            }
        }
    }
}