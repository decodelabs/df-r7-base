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
class LogicException extends \LogicException implements IException {}

interface IForcedResponse {
    public function setResponse($response);
    public function getResponse();
}

class ForcedResponse extends \Exception implements IForcedResponse {

    protected $_response;

    public function __construct($response) {
        $this->setResponse($response);
        parent::__construct('forced response');
    }

    public function setResponse($response) {
        $this->_response = $response;
        return $this;
    }

    public function getResponse() {
        return $this->_response;
    }
}


interface IResponseForcer {
    public function forceResponse($response);
}

trait TResponseForcer {
    
    public function forceResponse($response) {
        throw new ForcedResponse($response);
    }
}


// Interfaces
interface IAccess extends user\IState {}

interface IContext extends core\IContext, core\i18n\translate\ITranslationProxy, IResponseForcer {
    
    // Application
    public function spawnInstance($request=null, $copyRequest=false);
    public function getDispatchContext();
    public function isDispatchContext();
    
    // Requests
    public function getRequest();
    public function getLocation();
    public function normalizeOutputUrl($uri, $toRequest=false, $from=null, $to=null);
}


interface IDirectoryHelper extends core\IContextAware, core\IHelper {}

trait TDirectoryHelper {

    use core\TContextAware;

    public function __construct(IContext $context) {
        $this->_context = $context;
        $this->_init();
    }

    protected function _init() {}
}



interface IDirectoryRequestApplication extends core\IApplication, core\IContextAware {
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
    public function getControllerParts();
    public function getRawController();
    public function getRawControllerParts();
    public function isController($controller);
    public static function getDefaultController();
    public function isDefaultController();
    public static function formatController($controller);
    public static function formatControllerParts(array $parts);
    
    // Action
    public function setAction($action);
    public function getAction();
    public function getRawAction();
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
    
    public function getComponents();
    public function toSlug();

    // Match
    public function eq($request);
    public function matches($request);
    public function containsPath($request);
    public function contains($request);
    public function isPathWithin($request);
    public function isWithin($request);
    
    public function getLiteralPath();
    public function getLiteralPathArray();
    public function getLiteralPathString();
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



interface IController extends core\IContextAware, IResponseForcer {
    public function isControllerInline();
}


interface IAction extends core\IContextAware, user\IAccessLock, IResponseForcer {
    public function dispatch();
    public function getController();
    public function getActionMethodName();
    public function handleException(\Exception $e);
}

interface IComponent extends core\IContextAware, aura\view\IDeferredRenderable, user\IAccessLock, aura\view\ICascadingHelperProvider {
    public function getName();
}


trait TDirectoryAccessLock {

    use user\TAccessLock;
    
    public function getAccessLockDomain() {
        return 'directory';
    }
    
    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_context->location->lookupAccessKey($keys, $action);
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
        return $this->_context->location->getAccessLockId();
    }
}
