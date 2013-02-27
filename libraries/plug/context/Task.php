<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\plug;
use df\halo;
use df\arch;
    
class Task implements arch\IContextHelper {

    use arch\TContextHelper;

    public function launch($request) {
        return halo\process\Base::launchTask($request);
    }

    public function capture($request) {
        $request = arch\Request::factory($request);
        $application = core\application\Base::factory('Task');
        $application->setTaskRequest($request);

        $output = df\Launchpad::runApplication($application);
        core\dump($output);
    }

    public function getResponse() {
        $application = $this->_context->getApplication();

        if($application instanceof core\application\Task) {
            return $application->getTaskResponse();
        }

        $key = halo\task\Response::REGISTRY_KEY;

        if(!$output = $application->_getCacheObject($key)) {
            $output = halo\task\Response::defaultFactory();
            $application->_setCacheObject($output);
        }

        return $output;
    }
}