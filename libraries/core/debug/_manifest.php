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

interface IContext extends core\log\IGroupNode, core\log\IHandler {
    public function render();
    public function flush();
    public function execute();
}


interface IStackTrace extends core\log\INode, core\IArrayProvider {
    public function getCalls();
    public function toJsonArray();
    public function toJson();

    public function setMessage(string $message=null);
    public function getMessage();
}

interface IStackCall extends ILocationProvider, core\IArrayProvider {

    const STATIC_METHOD = 1;
    const OBJECT_METHOD = 2;
    const NAMESPACE_FUNCTION = 3;
    const GLOBAL_FUNCTION = 4;

    public function getArgs();
    public function hasArgs();
    public function countArgs();
    public function getArgString();

    public function getType();
    public function getTypeString();
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

    public function toJsonArray();
    public function toJson();
}

interface IRenderer {
    public function render();
}