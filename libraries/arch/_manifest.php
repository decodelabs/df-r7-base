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
use df\flow;


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

interface IContext extends core\IContext, IResponseForcer {

    // Application
    public function spawnInstance($request=null, $copyRequest=false);
    public function getDispatchContext();
    public function isDispatchContext();

    // Requests
    public function getRequest();
    public function getLocation();

    public function extractDirectoryLocation(&$path);
    public function extractThemeId(&$path, $findDefault=false);

    public function getScaffold();
}

interface IRequestOrientedApplication extends core\IApplication {
    public function getDispatchRequest();
}


interface IDirectoryHelper extends core\IContextAware, core\IHelper {}

trait TDirectoryHelper {

    use core\TContextAware;

    public function __construct(core\IContext $context, $target) {
        if(!$context instanceof arch\IContext) {
            if($target instanceof arch\IContext) {
                $context = $target;
                $target = null;
            } else {
                $context = Context::getCurrent();

                if(!$context) {
                    throw new RuntimeException(
                        'No arch context is available for '.__CLASS__.' helper'
                    );
                }
            }
        }

        $this->context = $context;

        if($target !== null && method_exists($this, '_handleHelperTarget')) {
            $this->_handleHelperTarget($target);
        }

        $this->_init();
    }

    protected function _init() {}
}


interface IRouter {
    public function routeIn(arch\IRequest $request);
    public function routeOut(arch\IRequest $request);
}



interface IRequest extends core\uri\IUrl, user\IAccessLock, \ArrayAccess {
    // Area
    public function setArea(string $area);
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
    public static function formatController($controller);
    public static function formatControllerParts(array $parts);

    // Node
    public function setNode(string $node=null);
    public function getNode();
    public function getRawNode();
    public function isNode($node);
    public static function getDefaultNode();
    public function isDefaultNode();
    public static function formatNode($node);

    // Type
    public function setType(string $type=null);
    public function getType();
    public function isType($type);
    public static function getDefaultType();
    public function isDefaultType();
    public static function formatType($type);

    public function getComponents();
    public function toSlug();

    // Match
    public function eq($request);
    public function pathEq($request);

    public function matches($request);
    public function matchesPath($request);

    public function contains($request);
    public function containsPath($request);

    public function isWithin($request);
    public function isPathWithin($request);

    public function getLiteralPath();
    public function getLiteralPathArray();
    public function getLiteralPathString();
    public function getDirectoryLocation();
    public function getLibraryPath();
    public function toReadableString();

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
    public function extractRelative($path);


    // Rewrite
    public function rewriteQueryToPath(...$keys);
    public function rewritePathToQuery($rootCount, ...$keys);
}

interface IProxyResponse {
    public function toResponse();
}


interface IOptionalDirectoryAccessLock {
    public function shouldCheckAccess(bool $flag=null);
}

trait TOptionalDirectoryAccessLock {

    private $_shouldCheckAccess = null;

    public function shouldCheckAccess(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldCheckAccess = $flag;
            return $this;
        }

        if($this->_shouldCheckAccess !== null) {
            return (bool)$this->_shouldCheckAccess;
        }

        if(is_bool(static::CHECK_ACCESS)) {
            return static::CHECK_ACCESS;
        }

        if(static::DEFAULT_ACCESS === IAccess::ALL) {
            return false;
        }

        return true;
    }
}

interface IController extends core\IContextAware, IResponseForcer, IOptionalDirectoryAccessLock {
    public function isControllerInline();
}



interface ITransformer extends core\IContextAware {
    public function canDeliver();
    public function execute();

    public function getSitemapEntries();
}

interface IComponent extends
    core\IContextAware,
    core\lang\IChainable,
    aura\view\IDeferredRenderable,
    user\IAccessLock,
    aura\view\ICascadingHelperProvider,
    arch\IProxyResponse,
    aura\view\ISlotContainer,
    \ArrayAccess {
    public function getName();
}


trait TDirectoryAccessLock {

    use user\TAccessLock;

    //const DEFAULT_ACCESS = null;
    //const ACCESS_SIGNIFIERS = null;

    public function getAccessLockDomain() {
        return 'directory';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->context->location->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return $this->_getClassDefaultAccess();
    }

    protected function _getClassDefaultAccess() {
        if($this instanceof IOptionalDirectoryAccessLock
        && !$this->shouldCheckAccess()) {
            return arch\IAccess::ALL;
        }

        if(defined('static::DEFAULT_ACCESS') && static::DEFAULT_ACCESS !== null) {
            return static::DEFAULT_ACCESS;
        }

        return DirectoryAccessController::$defaultAccess;
    }

    public function getAccessSignifiers(): array {
        if(defined('static::ACCESS_SIGNIFIERS')
        && is_array(static::ACCESS_SIGNIFIERS)) {
            return static::ACCESS_SIGNIFIERS;
        }

        return [];
    }

    public function getAccessLockId() {
        return $this->context->location->getAccessLockId();
    }
}

class DirectoryAccessController {
    public static $defaultAccess = arch\IAccess::NONE;
}
