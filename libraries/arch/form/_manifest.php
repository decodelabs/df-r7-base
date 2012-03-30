<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;
use df\aura;

// Exceptions
interface IException extends arch\IException {}
class RuntimeException extends \RuntimeException implements IException {}
class DelegateException extends RuntimeException {}
class EventException extends RuntimeException {}


// Interfaces
interface IForm {
    public function getStateController();
    public function loadDelegate($id, $name, $request=null);
    public function getDelegate($id);
    public function handleEvent($name, array $args=array());
    public function isValid();
}

interface IAction extends arch\IAction, IForm {
    
}

interface IDelegate extends IForm, arch\IContextAware {
    public function initialize();
    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content);
}

interface IStateController {
    public function getSessionId();
    public function getValues();
    
    public function getDelegateState($id);
    
    public function isNew($flag=null);
    public function reset();
}



