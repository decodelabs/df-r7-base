<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/log/_manifest');

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface ILocationProvider {
    public function getFile();
    public function getLine();
}

trait TLocationProvider {
    
    protected $_file;
    protected $_line;
    
    public function getFile() {
        return $this->_file;
    }
    
    public function getLine() {
        return $this->_line;
    }
}

interface IEntryPoint {
    public function dump($arg1);
    public function dumpDeep($arg1);
    public function exception(\Exception $exception);
    public function info($message);
    public function todo($message);
    public function warning($message);
    public function error($message);
    public function deprecated();
    public function stub();
    public function stackTrace($rewind=0);
}

interface IContext extends core\log\IGroupNode {
    public function setTransport(ITransport $transport);
    public function getTransport();
    public function flush();
}



interface IStackTrace extends core\log\INode, core\IArrayProvider {
    
}

interface IStackCall extends ILocationProvider {

    const STATIC_METHOD = 1;
    const OBJECT_METHOD = 2;
    const NAMESPACE_FUNCTION = 3;
    const GLOBAL_FUNCTION = 4;

    public function getArgs();
    public function hasArgs();
    public function countArgs();
    public function getArgString();
    
    public function getType();
    public function isStatic();
    public function isObject();
    public function isNamespaceFunction();
    public function isGlobalFunction();
    
    public function getNamespace();
    public function hasNamespace();
    
    public function getClass();
    public function hasClass();
    public function getClassName();
    
    public function getFunctionName();
    public function getSignature($argString=false);
    
    public function getCallingFile();
    public function getCallingLine();
}

interface ITransport {
    public function execute(IContext $context);
}


interface IRenderer {
    public function render();
}