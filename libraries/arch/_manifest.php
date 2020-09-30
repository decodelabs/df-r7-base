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

use DecodeLabs\Tagged\MarkupProxy;

// Exceptions
interface IForcedResponse
{
    public function setResponse($response);
    public function getResponse();
}

class ForcedResponse extends \Exception implements IForcedResponse
{
    protected $_response;

    public function __construct($response)
    {
        $this->setResponse($response);
        parent::__construct('forced response');
    }

    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }

    public function getResponse()
    {
        return $this->_response;
    }
}


interface IResponseForcer
{
    public function forceResponse($response);
}

trait TResponseForcer
{
    public function forceResponse($response)
    {
        throw new ForcedResponse($response);
    }
}


// Interfaces
interface IAccess extends user\IState
{
}

interface IContext extends core\IContext, IResponseForcer
{
    public static function factory($location=null, $runMode=null, $request=null): IContext;

    // Application
    public function spawnInstance($request=null, bool $copyRequest=false): IContext;
    public function getDispatchContext(): core\IContext;
    public function isDispatchContext(): bool;

    // Requests
    public function getRequest(): IRequest;
    public function getLocation(): IRequest;

    public function extractDirectoryLocation(string &$path): IRequest;
    public function extractThemeId(string &$path, bool $findDefault=false): ?string;

    public function getScaffold(): arch\scaffold\IScaffold;
}

interface IRequestOrientedRunner extends core\IRunner
{
    public function getDispatchRequest(): ?arch\IRequest;
}


interface IDirectoryHelper extends core\IContextAware, core\IHelper
{
}

trait TDirectoryHelper
{
    use core\TContextAware;

    public function __construct(core\IContext $context, $target)
    {
        if (!$context instanceof arch\IContext) {
            if ($target instanceof arch\IContext) {
                $context = $target;
                $target = null;
            } else {
                $context = Context::getCurrent();
            }
        }

        $this->context = $context;

        if ($target !== null && method_exists($this, '_handleHelperTarget')) {
            $this->_handleHelperTarget($target);
        }

        $this->_init();
    }

    protected function _init()
    {
    }
}


class Helper implements IDirectoryHelper, aura\view\IContextSensitiveHelper
{
    use arch\TDirectoryHelper, core\TContextProxy {
        core\TContextProxy::getContext insteadof arch\TDirectoryHelper;
        core\TContextProxy::hasContext insteadof arch\TDirectoryHelper;
    }
}


interface IRouter
{
    public function routeIn(arch\IRequest $request);
    public function routeOut(arch\IRequest $request);
}



interface IRequest extends
    core\uri\IUrl,
    core\uri\IPathContainer,
    core\uri\IQueryContainer,
    core\uri\IFragmentContainer,
    user\IAccessLock,
    \ArrayAccess
{
    const AREA_MARKER = '~';
    const DEFAULT_AREA = 'front';
    const DEFAULT_NODE = 'index';
    const DEFAULT_TYPE = 'html';

    const REDIRECT_FROM = 'rf';
    const REDIRECT_TO = 'rt';

    // Area
    public function setArea(string $area);
    public function getArea();
    public function isArea($area): bool;
    public static function getDefaultArea();
    public function isDefaultArea(): bool;
    public static function formatArea($area);

    // Controller
    public function setController($controller);
    public function getController();
    public function getControllerParts();
    public function getRawController();
    public function getRawControllerParts();
    public function isController($controller): bool;
    public static function formatController($controller);
    public static function formatControllerParts(array $parts);

    // Node
    public function setNode(string $node=null);
    public function getNode();
    public function getRawNode();
    public function isNode($node): bool;
    public static function getDefaultNode();
    public function isDefaultNode(): bool;
    public static function formatNode($node);

    // Type
    public function setType(string $type=null);
    public function getType();
    public function isType($type): bool;
    public static function getDefaultType();
    public function isDefaultType(): bool;
    public static function formatType($type);

    public function getComponents();
    public function toSlug();

    // Match
    public function eq($request): bool;
    public function pathEq($request): bool;

    public function matches($request): bool;
    public function matchesPath($request): bool;

    public function contains($request): bool;
    public function containsPath($request): bool;

    public function isWithin($request): bool;
    public function isPathWithin($request): bool;

    public function getLiteralPath();
    public function getLiteralPathArray(bool $incType=true, bool $incNode=true);
    public function getLiteralPathString();
    public function getDirectoryLocation();
    public function getLibraryPath();
    public function toReadableString();
    public function normalize();

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


interface IProxyResponse
{
    public function toResponse();
}


interface IOptionalDirectoryAccessLock
{
    public function shouldCheckAccess(bool $flag=null);
}

trait TOptionalDirectoryAccessLock
{
    private $_shouldCheckAccess = null;

    public function shouldCheckAccess(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_shouldCheckAccess = $flag;
            return $this;
        }

        if ($this->_shouldCheckAccess !== null) {
            return (bool)$this->_shouldCheckAccess;
        }

        if (is_bool(static::CHECK_ACCESS)) {
            return static::CHECK_ACCESS;
        }

        if (static::DEFAULT_ACCESS === IAccess::ALL) {
            return false;
        }

        return true;
    }
}


interface ITransformer extends core\IContextAware
{
    public function canDeliver();
    public function execute();

    public function getSitemapEntries(): iterable;
}

interface IComponent extends
    core\IContextAware,
    core\lang\IChainable,
    aura\view\IDeferredRenderable,
    user\IAccessLock,
    aura\view\ICascadingHelperProvider,
    arch\IProxyResponse,
    aura\view\ISlotContainer,
    MarkupProxy,
    \ArrayAccess
{
    public static function factory(arch\IContext $context, $name, array $args=null): arch\IComponent;
    public function getName(): string;
}


trait TDirectoryAccessLock
{
    use user\TAccessLock;

    //const DEFAULT_ACCESS = null;
    //const ACCESS_SIGNIFIERS = null;

    public function getAccessLockDomain()
    {
        return 'directory';
    }

    public function lookupAccessKey(array $keys, $action=null)
    {
        return $this->context->location->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null)
    {
        return $this->_getClassDefaultAccess();
    }

    protected function _getClassDefaultAccess()
    {
        if ($this instanceof IOptionalDirectoryAccessLock
        && !$this->shouldCheckAccess()) {
            return arch\IAccess::ALL;
        }

        if (defined('static::DEFAULT_ACCESS') && static::DEFAULT_ACCESS !== null) {
            return static::DEFAULT_ACCESS;
        }

        return DirectoryAccessController::$defaultAccess;
    }

    public function getAccessSignifiers(): array
    {
        if (defined('static::ACCESS_SIGNIFIERS')
        && is_array(static::ACCESS_SIGNIFIERS)) {
            return static::ACCESS_SIGNIFIERS;
        }

        return [];
    }

    public function getAccessLockId()
    {
        return $this->context->location->getAccessLockId();
    }
}

class DirectoryAccessController
{
    public static $defaultAccess = arch\IAccess::NONE;
}



// Ajax
interface IAjaxDataProvider
{
    public function setAjaxData(array $data);
    public function addAjaxData(array $data);
    public function getAjaxData(): array;
    public function setAjax(string $key, $value);
    public function getAjax(string $key);
    public function hasAjax(string $key): bool;
    public function removeAjax(string $key);
    public function clearAjax();
}

trait TAjaxDataProvider
{
    protected $_ajax = [];

    public function setAjaxData(array $data)
    {
        $this->_ajax = $data;
        return $this;
    }

    public function addAjaxData(array $data)
    {
        foreach ($data as $key => $value) {
            $this->setAjax($key, $value);
        }

        return $this;
    }

    public function getAjaxData(): array
    {
        return $this->_ajax;
    }

    public function setAjax(string $key, $value)
    {
        $this->_ajax[$key] = $value;
        return $this;
    }

    public function getAjax(string $key)
    {
        return $this->_ajax[$key] ?? null;
    }

    public function hasAjax(string $key): bool
    {
        return isset($this->_ajax[$key]);
    }

    public function removeAjax(string $key)
    {
        unset($this->_ajax[$key]);
        return $this;
    }

    public function clearAjax()
    {
        $this->_ajax = [];
        return $this;
    }
}


interface IMail extends aura\view\IView, flow\mail\IMessage
{
    public static function factory(arch\IContext $context, $path): arch\IMail;

    public function getName(): string;
    public function getDescription();
    public function preparePreview();
}
