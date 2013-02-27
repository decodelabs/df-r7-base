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
    
abstract class Action extends arch\Action {

    public $response;

    public function __construct(arch\IContext $context, arch\IController $controller=null) {
        parent::__construct($context, $controller);

        $this->response = $this->task->getResponse();
        $this->_init();
    }

    protected function _init() {}

    final public function execute() {
        try {
            $output = $this->_run();
        } catch(\Exception $e) {
            $output = $this->_handleException($e);
        }

        return $output;
    }

    abstract protected function _run();

    protected function _handleException(\Exception $e) {
        throw $e;
    }
}