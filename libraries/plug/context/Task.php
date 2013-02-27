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

        return df\Launchpad::runApplication($application);
    }

    public function getResponse() {
        $application = $this->_context->getApplication();

        if($application instanceof core\application\Task) {
            return $application->getTaskResponse();
        }

        $key = core\io\Multiplexer::REGISTRY_KEY.':task';

        if(!$output = $application->getRegistryObject($key)) {
            $output = arch\task\Response::defaultFactory();
            $application->setRegistryObject($output);
        }

        return $output;
    }
}