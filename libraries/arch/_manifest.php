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


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class HelperNotFoundException extends RuntimeException {}


// Interfaces
interface IContext extends core\IApplicationAware, core\i18n\translate\ITranslationProxy, core\IHelperProvider {
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

interface IContextHelper extends IContextAware, core\IHelper {}



interface IDirectoryRequestApplication extends core\IApplication, IContextAware {}

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
}

interface IErrorRequest extends IRequest {
    public function getCode();
    public function getException();
    public function getLastRequest();
}

interface IController extends IContextAware, user\IAccessLock {
    public function getType();
    public function isControllerInline();
}


interface IAction extends IContextAware, user\IAccessLock {
    public function dispatch();
    public function isActionInline();
}

interface IComponent extends IContextAware, user\IAccessLock {
    public function getName();
}
