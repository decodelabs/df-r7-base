<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\application\_actions;

use df;
use df\core;
use df\apex;
use df\halo;
use df\arch;
    
class TaskGenerateEntries extends arch\task\Action {

    public function execute() {
        $phpPath = core\Environment::getInstance()->getPhpBinaryPath();
        
        if($phpPath == 'php') {
            $phpPath = halo\system\Base::getInstance()->which('php');
        }
        
        $appPath = df\Launchpad::$applicationPath;
        $environmentId = df\Launchpad::$environmentId;

        if($buildId = $this->request->query['build']) {
            if(substr($buildId, -8) == '-testing') {
                $this->io->writeLine('Generating testing entry points');
                $modes = ['testing'];
            } else {
                $this->io->writeLine('Generating testing and production entry points');
                $modes = ['testing', 'production'];
            }

            foreach($modes as $mode) {
                $entryPath = $appPath.'/entry/'.$environmentId.'.'.$mode.'.php';
                
                $data = '<?php'."\n\n".
                        '/* This file is automatically generated by the DF package builder */'."\n".
                        'require_once dirname(__DIR__).\'/data/local/run/'.$buildId.'/Df.php\';'."\n";

                $data .= 'df\\Launchpad::runAs(\''.$environmentId.'\', '.($mode != 'production' ? 'true' : 'false').', dirname(__DIR__));';
                file_put_contents($entryPath, $data);

                try {
                    core\io\Util::chmod($entryPath, 0777, true);
                } catch(\Exception $e) {}
            }
        }

        $this->runChild('application/generate-base-entry');
    }
}