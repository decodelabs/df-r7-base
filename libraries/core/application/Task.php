<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\arch;
use df\halo;

class Task extends Base implements arch\IDirectoryRequestApplication {
    
    const RUN_MODE = 'Task';
    
    protected $_context;
    protected $_request;
    protected $_response;


    public function getDefaultDirectoryAccess() {
        return arch\IAccess::ALL;
    }
    
// Request
    public function setTaskRequest(arch\IRequest $request) {
        $this->_request = $request;
        return $this;
    }
    
    public function getTaskRequest() {
        if(!$this->_request) {
            throw new core\RuntimeException(
                'The task request is not available until the application has been dispatched'
            );
        }
        
        return $this->_request;
    }

    
// Response
    public function setTaskResponse(core\io\Multiplexer $response) {
        $this->_response = $response;
        return $this;
    }

    public function getTaskResponse() {
        if(!$this->_response) {
            $this->_response = core\io\Multiplexer::defaultFactory('task');
        }

        return $this->_response;
    }
    
    
// Context
    public function getContext() {
        if(!$this->_context) {
            throw new core\RuntimeException(
                'A context is not available until the application has been dispatched'
            );
        }
        
        return $this->_context;
    }

    public function hasContext() {
        return $this->_context !== null;
    }
    
    
    
// Execute
    public function dispatch(arch\IRequest $request=null) {
        $this->_beginDispatch();
        
        if($request !== null) {
            $this->_request = $request;
        } else if(!$this->_request) {
            $command = core\cli\Command::fromArgv();
            
            if(!$arg = $command[2]) {
                throw new core\InvalidArgumentException(
                    'No task path has been specified'
                );
            }

            $this->_request = arch\Request::factory($arg);
        }
        
        $response = false;
        $previousError = false;
        $request = $this->_request;
        
        set_time_limit(0);
        
        while(true) {
            try {
                while(true) {
                    $response = $this->_dispatchRequest($request);

                    if($response instanceof arch\IRequest) {
                        $request = $response;
                        continue;
                    }
                    
                    break;
                }
            } catch(\Exception $e) {
                if($previousError) {
                    throw $previousError;
                }
                
                while(ob_get_level()) {
                    ob_end_clean();
                }
                
                $previousError = $e;
                $response = null;
                
                try {
                    $request = clone $this->_context->request;
                } catch(\Exception $e) {
                    $request = null;
                }
                
                $request = new arch\ErrorRequest($e->getCode(), $e, $request);
                continue;
            }
            
            break;
        }

        return $response;
    }
    
    
    protected function _dispatchRequest(arch\IRequest $request) {
        $this->_context = arch\Context::factory($this, clone $request);
        $this->_context->request = $request;
        $action = arch\Action::factory($this->_context);
        $response = $action->dispatch();
        
        // Forwarding
        if($response instanceof arch\IRequest) {
            return $response;
        }

        if($response === null) {
            $response = $this->_response;
        }
        
        return $response;
    }
    
    
    public function launchPayload($payload) {
        if(is_string($payload)) {
            echo $payload."\r\n";
        } else if($payload instanceof core\io\IFlushable) {
            $payload->flush();
        } else if(!empty($payload)) {
            core\stub($payload);
        }
    }
}
