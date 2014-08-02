<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\task;

use df;
use df\core;
use df\arch;
use df\halo;
    
abstract class Action extends arch\Action implements IAction {

    public $response;

    public function __construct(arch\IContext $context, arch\IController $controller=null) {
        parent::__construct($context, $controller);
        $this->_init();
    }

    protected function _init() {}

    public function dispatch() {
        if(!$this->response) {
            $this->response = $this->task->getResponse();
        }

        return parent::dispatch();
    }

    public function runChild($request) {
        $request = arch\Request::factory($request);
        $context = $this->_context->spawnInstance($request, true);
        $action = arch\Action::factory($context);

        if(!$action instanceof self) {
            $this->throwError(500, 'Child action '.$request.' does not extend arch\\task\\Action');
        }

        return $action->dispatch();
    }
}