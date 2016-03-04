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

class Task extends Base implements core\IContextAware, arch\IRequestOrientedApplication {

    const RUN_MODE = 'Task';

    protected $_context;
    protected $_dispatchRequest;
    protected $_command;
    protected $_multiplexer;

// Request
    public function getDispatchRequest() {
        return $this->_dispatchRequest;
    }


// Command
    public function getCommand() {
        if(!$this->_command) {
            throw new core\RuntimeException(
                'The task command is not available until the application has been dispatched'
            );
        }

        return $this->_command;
    }


// Response
    public function setMultiplexer(core\io\Multiplexer $multiplexer) {
        $this->_multiplexer = $multiplexer;
        return $this;
    }

    public function getMultiplexer() {
        if(!$this->_multiplexer) {
            $this->_multiplexer = core\io\Multiplexer::defaultFactory('task');
        }

        return $this->_multiplexer;
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
    public function dispatch() {
        arch\DirectoryAccessController::$defaultAccess = arch\IAccess::ALL;

        $request = $this->_prepareRequest();
        $response = $this->_dispatchRequest($request, $this->_command);
        $this->_handleResponse($response);
    }


// Prepare command
    protected function _prepareRequest() {
        $args = null;

        $command = core\cli\Command::fromArgv();
        $args = array_slice($command->getArguments(), 1);
        $request = array_shift($args);

        if(strtolower($request) == 'task') {
            $request = array_shift($args);
        }

        if(!$request) {
            throw new core\InvalidArgumentException(
                'No task path has been specified'
            );
        }

        $request = arch\Request::factory($request);
        $this->_command = new core\cli\Command(df\Launchpad::$environmentId.'.'.df\Launchpad::getEnvironmentMode().'.php');

        if($args) {
            foreach($args as $arg) {
                $this->_command->addArgument($arg);
            }
        }

        return $request;
    }


// Dispatch request
    protected function _dispatchRequest(arch\IRequest $request, core\cli\ICommand $command=null) {
        set_time_limit(0);
        $this->_dispatchRequest = clone $request;

        try {
            $response = $this->_dispatchNode($request, $command);
        } catch(\Throwable $e) {
            while(ob_get_level()) {
                ob_end_clean();
            }

            $this->_dispatchException = $e;

            try {
                $response = $this->_dispatchNode(new arch\Request('error/'));
            } catch(\Throwable $f) {
                throw $e;
            }
        }

        return $response;
    }


// Dispatch node
    protected function _dispatchNode(arch\IRequest $request, core\cli\ICommand $command=null) {
        $this->_context = arch\Context::factory(clone $request);
        $this->_context->request = $request;

        $node = arch\node\Base::factory($this->_context);

        if($command && ($node instanceof arch\node\ITaskNode)) {
            $node->extractCliArguments($command);
        }


        foreach($this->_registry as $object) {
            if($object instanceof core\IDispatchAware) {
                $object->onApplicationDispatch($node);
            }
        }

        return $node->dispatch();
    }


// Handle response
    protected function _handleResponse($response) {
        // Callback
        if($response instanceof \Closure
        || $response instanceof core\lang\ICallback) {
            $response = $response();
        }

        // Forwarding
        if($response instanceof arch\IRequest) {
            $this->_context->throwError(500, 'Request forwarding is no longer supported');
        }

        if($response === null) {
            $response = $this->_multiplexer;
        }

        if(df\Launchpad::$debug) {
            df\Launchpad::$debug->execute();
        }

        if(is_string($response)) {
            echo $response."\r\n";
        } else if($response instanceof core\io\IFlushable) {
            $response->flush();
        } else if(!empty($response)) {
            core\stub($response);
        }
    }


// Debug
    public function renderDebugContext(core\debug\IContext $context) {
        df\Launchpad::loadBaseClass('core/debug/renderer/PlainText');
        $output = (new core\debug\renderer\PlainText($context))->render();

        $response = $this->getMultiplexer();
        $response->writeError($output);

        return $this;
    }
}
