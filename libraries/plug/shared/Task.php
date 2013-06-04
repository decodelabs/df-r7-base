<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\shared;

use df;
use df\core;
use df\plug;
use df\halo;
use df\arch;
    
class Task implements core\ISharedHelper {

    use core\TSharedHelper;

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
        if($this->_context->application instanceof core\application\Task) {
            return $this->_context->application->getTaskResponse();
        }

        $key = core\io\Multiplexer::REGISTRY_KEY.':task';

        if(!$output = $this->_context->application->getRegistryObject($key)) {
            $output = arch\task\Response::defaultFactory();
            $this->_context->application->setRegistryObject($output);
        }

        return $output;
    }
}