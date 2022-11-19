<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch\Dumpable;

use DecodeLabs\R7\Legacy;

use df\arch;
use df\arch\scaffold\Loader as ScaffoldLoader;
use df\aura;
use df\core;
use df\link;

class Base implements INode, Dumpable
{
    use core\TContextProxy;
    use arch\TDirectoryAccessLock;
    use arch\TResponseForcer;

    public const OPTIMIZE = false;

    public const DEFAULT_ACCESS = null;
    public const CHECK_ACCESS = null;
    public const ACCESS_SIGNIFIERS = null;

    public const SITEMAP = false;

    private $_shouldOptimize = null;
    private $_shouldCheckAccess = null;
    private $_defaultAccess = null;
    private $_accessSignifiers = null;
    private $_callback;

    public static function factory(arch\IContext $context): INode
    {
        $class = self::getClassFor(
            $context->location,
            Genesis::$kernel->getMode(),
            $isDefault
        );

        if (!$class || $isDefault) {
            try {
                $scaffold = ScaffoldLoader::fromContext($context);
                return $scaffold->loadNode();
            } catch (arch\scaffold\NotFoundException $e) {
            }
        }

        if (!$class) {
            if ($node = arch\Transformer::loadNode($context)) {
                return $node;
            }

            throw Exceptional::NotFound([
                'message' => 'No node could be found for ' . utf8_encode($context->location->toString()),
                'http' => 404
            ]);
        }

        return new $class($context);
    }

    public static function getClassFor(arch\IRequest $request, $runMode = 'Http', &$isDefault = null)
    {
        $runMode = ucfirst($runMode);
        $parts = $request->getControllerParts();
        $parts[] = '_nodes';
        $parts[] = $runMode . ucfirst($request->getNode());
        $end = implode('\\', $parts);

        if (false !== strpos($end, '\\\\') || substr($end, 0, 1) == '\\') {
            return null;
        }

        $class = 'df\\apex\\directory\\' . $request->getArea() . '\\' . $end;
        $isDefault = false;

        if (!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\' . $end;

            if (!class_exists($class)) {
                array_pop($parts);
                $parts[] = $runMode . 'Default';
                $end = implode('\\', $parts);
                $isDefault = true;

                $class = 'df\\apex\\directory\\' . $request->getArea() . '\\' . $end;

                if (!class_exists($class)) {
                    $class = 'df\\apex\\directory\\shared\\' . $end;

                    if (!class_exists($class)) {
                        $class = null;
                    }
                }
            }
        }

        return $class;
    }

    public function __construct(arch\IContext $context, $callback = null)
    {
        $this->context = $context;
        $this->setCallback($callback);
    }

    public function setCallback($callback)
    {
        $this->_callback = core\lang\Callback::factory($callback);
        return $this;
    }

    public function getCallback(): ?callable
    {
        return $this->_callback;
    }

    public function shouldOptimize(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldOptimize = $flag;
            return $this;
        }

        if ($this->_shouldOptimize !== null) {
            return (bool)$this->_shouldOptimize;
        }

        return (bool)static::OPTIMIZE;
    }

    public function shouldCheckAccess(bool $flag = null)
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

        if ($this->_defaultAccess === arch\IAccess::ALL) {
            return false;
        }

        return !$this->shouldOptimize();
    }

    public function setDefaultAccess($access)
    {
        $this->_defaultAccess = $access;
        return $this;
    }

    public function getDefaultAccess($action = null)
    {
        if ($this->_defaultAccess !== null) {
            return $this->_defaultAccess;
        }

        return $this->_getClassDefaultAccess();
    }

    public function setAccessSignifiers(string ...$signifiers)
    {
        if (empty($signifiers)) {
            $signifiers = null;
        }

        $this->_accessSignifiers = $signifiers;
        return $this;
    }

    public function getAccessSignifiers(): array
    {
        if ($this->_accessSignifiers !== null) {
            return $this->_accessSignifiers;
        } elseif (is_array(static::ACCESS_SIGNIFIERS)) {
            return static::ACCESS_SIGNIFIERS;
        } else {
            return [];
        }
    }


    // Dispatch
    public function dispatch()
    {
        $output = null;
        $func = null;

        if ($this->shouldCheckAccess()) {
            $client = $this->context->user->getClient();

            if ($client->isDeactivated()) {
                throw Exceptional::Forbidden([
                    'message' => 'Client deactivated',
                    'http' => 403
                ]);
            }

            if (!$client->canAccess($this)) {
                throw Exceptional::Unauthorized([
                    'message' => 'Insufficient permissions',
                    'http' => 401
                ]);
            }
        }

        if (method_exists($this, '_beforeDispatch')) {
            try {
                $this->_beforeDispatch();
            } catch (arch\IForcedResponse $e) {
                $output = $e->getResponse();
            }
        }


        if ($output === null) {
            if ($this->_callback) {
                $output = $this->_callback->invoke($this);
            } else {
                $func = $this->getDispatchMethodName();

                if ($func !== null) {
                    try {
                        $output = $this->$func();
                    } catch (arch\IForcedResponse $e) {
                        $output = $e->getResponse();
                    } catch (\Throwable $e) {
                        $output = $this->handleException($e);
                    }
                } else {
                    $output = $this->_handleNoDispatchMethod();
                }
            }
        }

        if (method_exists($this, '_afterDispatch')) {
            try {
                $output = $this->_afterDispatch($output);
            } catch (arch\IForcedResponse $e) {
                $output = $e->getResponse();
            }
        }

        return $output;
    }

    public function getDispatchMethodName(): ?string
    {
        $type = $this->context->location->getType();

        if (Genesis::$kernel->getMode() == 'Http') {
            $mode = ucfirst(strtolower(Legacy::$http->getMethod()));

            if ($mode == 'Head') {
                $mode = 'Get';
            }
        } else {
            $mode = null;
        }


        if ($mode) {
            $func = 'execute' . $mode . 'As' . $type;

            if (method_exists($this, $func)) {
                return $func;
            }

            $func = 'execute' . $mode;

            if (method_exists($this, $func)) {
                return $func;
            }
        }

        $func = 'executeAs' . $type;

        if (method_exists($this, $func)) {
            return $func;
        }

        $func = 'execute';

        if (method_exists($this, $func)) {
            return $func;
        }

        return null;
    }

    protected function _handleNoDispatchMethod(): mixed
    {
        if ($this->context->request->getType() == 'Htm') {
            $request = clone $this->context->request->setType('Html');
            return Legacy::$http->redirect($request);
        }

        throw Exceptional::NotFound([
            'message' => 'No handler could be found for node: ' .
                $this->context->location->toString(),
            'http' => 404
        ]);
    }

    public function handleException(\Throwable $e)
    {
        throw $e;
    }


    public function executeAsAjax()
    {
        switch (Genesis::$kernel->getMode()) {
            case 'Http':
                if (method_exists($this, 'executeAsHtml')) {
                    $this->request->setType(null);
                    $output = $this->executeAsHtml();
                } elseif (method_exists($this, 'execute')) {
                    $output = $this->execute();
                } else {
                    $output = null;
                }

                if ($output instanceof aura\view\IView) {
                    return Legacy::$http->ajaxResponse($output);
                } elseif ($output instanceof link\http\IResponse) {
                    return $output;
                } elseif ($output !== null) {
                    return Legacy::$http->stringResponse(
                        $this->data->toJson([
                            'node' => $this->request->getLiteralPathString(),
                            'content' => (string)$output
                        ]),
                        'application/json'
                    );
                }
        }

        throw Exceptional::NotFound([
            'message' => 'No ajax content found',
            'http' => 404
        ]);
    }



    // Sitemap
    public function getSitemapEntries(): iterable
    {
        if (!static::SITEMAP) {
            return;
        }

        $parts = explode('\\', get_class($this));

        $name = (string)array_pop($parts);
        $parts = array_slice($parts, 3, -1);
        $parts[] = Dictum::actionSlug(substr($name, 4));

        yield new arch\navigation\SitemapEntry(
            $this->uri('~' . implode('/', $parts)),
            null,
            is_string(static::SITEMAP) ? static::SITEMAP : 'monthly'
        );
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*type' => Genesis::$kernel->getMode(),
            '*context' => $this->context
        ];
    }
}
