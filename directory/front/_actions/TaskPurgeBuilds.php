<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;
    
class TaskPurgeBuilds extends arch\Action {

    const CONTINGENCY = 1;

    public function execute() {
        $response = new halo\task\Response([
            new core\io\channel\Std()
        ]);

        $contingency = (int)$this->request->query->get('contingency', self::CONTINGENCY);

        if($contingency < 0) {
            $contingency = 0;
        }

        $appPath = df\Launchpad::$applicationPath;
        $runPath = $appPath.'/data/local/run';

        $response->writeLine('Purging old builds...');
        $response->writeLine('Keeping '.$contingency.' build(s) as contingency');

        $list = scandir($runPath);
        sort($list);
        unset($list[0], $list[1]);

        for($i = 0; $i < $contingency + 1; $i++) {
            array_pop($list);
        }

        foreach($list as $entry) {
            if(is_file($runPath.'/'.$entry)) {
                $response->writeLine('Deleting file build '.$entry);

                core\io\Util::deleteFile($runPath.'/'.$entry);
            } else if(is_dir($runPath.'/'.$entry)) {
                $response->writeLine('Deleting build '.$entry);

                core\io\Util::deleteDir($runPath.'/'.$entry);
            }
        }
    }
}