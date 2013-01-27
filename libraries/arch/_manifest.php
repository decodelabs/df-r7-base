<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\user;
use df\aura;


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class HelperNotFoundException extends RuntimeException {}


// Interfaces
interface IAccess extends user\IState {}

interface IContext extends core\IApplicationAware, core\IHelperProvider {
    public function spawnInstance($request);
    
    // Application
    public function getRunMode();
    public function getDispatchContext();
    public function isDispatchContext();
    
    // Requests
    public function getRequest();
    public function getDispatchRequest();
    public function normalizeOutputUrl($uri);
    
    // Locale
    public function setLocale($locale);
    public function getLocale();
    
    // Helpers
    public function throwError($code=500, $message='');
    public function findFile($path);
    public function getHelper($method);
    public function __get($key);
    
    public function getI18nManager();
    public function getPolicyManager();
    public function getSystemInfo();
    public function getUserManager();
}

interface IContextAware {
    public function getContext();
}

trait TContextAware {
    
    protected $_context;
    
    public function getContext() {
        return $this->_context;
    }
}


trait TContextProxy {
    
    use TContextAware;
    
    public function __call($method, $args) {
        return call_user_func_array(array($this->_context, $method), $args);
    }
    
    public function __get($key) {
        return $this->_context->__get($key);
    }
}

interface IContextHelper extends IContextAware, core\IHelper {}





interface IDirectoryRequestApplication extends core\IApplication, IContextAware {
    public function getDefaultDirectoryAccess();
}

interface IRoutedDirectoryRequestApplication extends IDirectoryRequestApplication {
    public function requestToUrl(IRequest $request);
    public function countRoutes();
    public function countRouteMatches();
}


interface IRouter {
    public function routeIn(arch\IRequest $request);
    public function routeOut(arch\IRequest $request);
}



interface IRequest extends core\uri\IUrl, user\IAccessLock {
    // Area
    public function setArea($area);
    public function getArea();
    public function isArea($area);
    public static function getDefaultArea();
    public function isDefaultArea();
    public static function formatArea($area);
    
    // Controller
    public function setController($controller);
    public function getController();
    public function isController($controller);
    public static function getDefaultController();
    public function isDefaultController();
    public static function formatController($controller);
    
    // Action
    public function setAction($action);
    public function getAction();
    public function isAction($action);
    public static function getDefaultAction();
    public function isDefaultAction();
    public static function formatAction($action);
    
    // Type
    public function setType($type);
    public function getType();
    public function isType($type);
    public static function getDefaultType();
    public function isDefaultType();
    public static function formatType($type);
    
    // Match
    public function eq(IRequest $request);
    
    public function getLiteralPath();
    public function getLiteralPathArray();
    public function getDirectoryLocation();
    
    // Redirect
    public function encode();
    public static function decode($str);
    
    public function setRedirect($from, $to=null);
    public function hasRedirectFrom();
    public function hasRedirectTo();
    public function setRedirectFrom($redir);
    public function getRedirectFrom();
    public function setRedirectTo($redir);
    public function getRedirectTo();
    
    // Parent
    public function getParent();


    // Rewrite
    public function rewriteQueryToPath($keys);
    public function rewritePathToQuery($rootCount, $keys);
}

interface IErrorRequest extends IRequest {
    public function getCode();
    public function getException();
    public function getLastRequest();
}


interface IProxyResponse {
    public function toResponse();
}



interface IController extends IContextAware, user\IAccessLock {
    public function isControllerInline();
    public function setActiveAction(IAction $action=null);
    public function getActiveAction();
}


interface IAction extends IContextAware, user\IAccessLock {
    public function dispatch();
    public function isActionInline();
    public function getController();

    public static function getActionMethodName($actionClass, IContext $context);
    public static function getControllerMethodName($controllerClass, IContext $context);
}

interface IComponent extends IContextAware, aura\view\IDeferredRenderable, user\IAccessLock {
    public function getName();
}


interface IFacetController extends IContextAware, IProxyResponse, core\IAttributeContainer, \ArrayAccess {
    public function setInitializer(Callable $initializer=null);
    public function getInitializer();

    public function setAction(Callable $action);
    public function getAction();

    public function addFacet($id, Callable $action);
    public function hasFacet($id);
    public function getFacet($id);
    public function removeFacet($id);
}



trait TDirectoryAccessLock {

    use user\TAccessLock;
    
    public function getAccessLockDomain() {
        return 'directory';
    }
    
    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_context->getRequest()->lookupAccessKey($keys, $action);
    }
    
    public function getDefaultAccess($action=null) {
        return $this->_getClassDefaultAccess();
    }

    protected function _getClassDefaultAccess() {
        if(!static::CHECK_ACCESS) {
            return arch\IAccess::ALL;
        }

        if(static::DEFAULT_ACCESS !== null) {
            return static::DEFAULT_ACCESS;
        }

        $application = $this->_context->getApplication();

        if($application instanceof IDirectoryRequestApplication) {
            return $application->getDefaultDirectoryAccess();
        }

        return arch\IAccess::NONE;
    }

    public function getAccessLockId() {
        return $this->_context->getRequest()->getAccessLockId();
    }
}
