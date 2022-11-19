<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use df\arch\IComponent as Component;
use df\arch\IContext as Context;
use df\arch\IRequest as DirectoryRequest;
use df\arch\navigation\menu\IMenu as Menu;
use df\arch\node\Base as BaseNode;
use df\arch\node\form\State as FormState;
use df\arch\node\IDelegate as Delegate;

use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\arch\node\INode as Node;
use df\arch\Scaffold;
use df\arch\scaffold\Component\Generic as GenericScaffoldComponent;
use df\arch\scaffold\Navigation\Menu as ScaffoldMenu;

use df\arch\scaffold\Section\Provider as SectionProvider;
use df\arch\TDirectoryAccessLock as DirectoryAccessLockTrait;
use df\arch\TOptionalDirectoryAccessLock as OptionalDirectoryAccessLockTrait;
use df\aura\view\Base as ViewBase;

use df\aura\view\TView_CascadingHelperProvider as CascadingHelperProviderTrait;
use df\core\TContextAware as ContextAwareTrait;

abstract class Generic implements Scaffold
{
    //use core\TContextProxy;
    use ContextAwareTrait;
    use CascadingHelperProviderTrait;
    use DirectoryAccessLockTrait;
    use OptionalDirectoryAccessLockTrait;

    public const TITLE = null;
    public const ICON = null;

    public const CHECK_ACCESS = true;
    public const DEFAULT_ACCESS = null;

    public const PROPAGATE_IN_QUERY = [];
    public const ACCESS_SIGNIFIERS = null;
    public const NAME_KEY_FIELD_MAX_LENGTH = 40;

    private $directoryKeyName;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->view = ViewBase::factory($context->request->getType(), $this->context);
    }


    // Registry
    public function getRegistryObjectKey(): string
    {
        return 'scaffold(' . $this->context->location->getPath()->getDirname() . ')';
    }


    // View
    public function getView()
    {
        return $this->view;
    }




    // Loaders
    public function loadNode(): Node
    {
        $node = $this->context->request->getNode();
        $method = lcfirst($node) . $this->context->request->getType() . 'Node';

        if (!method_exists($this, $method)) {
            $method = lcfirst($node) . 'Node';

            if (!method_exists($this, $method)) {
                $method = 'build' . ucfirst($node) . 'DynamicNode';

                if (method_exists($this, $method)) {
                    $node = $this->{$method}();

                    if ($node instanceof Node) {
                        return $node;
                    }
                }

                if (
                    $this instanceof SectionProvider &&
                    ($node = $this->loadSectionNode())
                ) {
                    return $node;
                }

                throw Exceptional::{'df/arch/node/NotFound,NotFound'}(
                    'Scaffold at ' . $this->context->location . ' cannot provide node ' . $node
                );
            }
        }

        return $this->generateNode([$this, $method]);
    }

    public function onNodeDispatch(Node $node)
    {
    }

    public function loadComponent(string $name, array $args = null): Component
    {
        $keyName = $this->getDirectoryKeyName();
        $origName = $name;

        if (substr($name, 0, strlen($keyName)) == ucfirst($keyName)) {
            $name = substr($name, strlen($keyName));
        }

        $method = 'generate' . $name . 'Component';

        if (!method_exists($this, $method) && $origName !== $name) {
            $method = 'generate' . $origName . 'Component';
            $activeName = $origName;
        } else {
            $activeName = $name;
        }

        if (method_exists($this, $method)) {
            return new GenericScaffoldComponent($this, $activeName, $args);
        }

        $method = 'build' . $name . 'Component';

        if (!method_exists($this, $method) && $origName !== $name) {
            $method = 'build' . $origName . 'Component';
        }

        if (method_exists($this, $method)) {
            $output = $this->{$method}($args);

            if (!$output instanceof Component) {
                throw Exceptional::{'df/arch/component/NotFound,NotFound'}(
                    'Scaffold at ' . $this->context->location . ' attempted but failed to provide component ' . $origName
                );
            }

            return $output;
        }

        throw Exceptional::{'df/arch/component/NotFound,NotFound'}(
            'Scaffold at ' . $this->context->location . ' cannot provide component ' . $origName
        );
    }

    public function loadFormDelegate(string $name, FormState $state, FormEventDescriptor $event, string $id): Delegate
    {
        $keyName = $this->getDirectoryKeyName();
        $origName = $name;

        if (substr($name, 0, strlen($keyName)) == ucfirst($keyName)) {
            $name = substr($name, strlen($keyName));
        }

        $method = 'build' . $name . 'FormDelegate';

        if (!method_exists($this, $method)) {
            throw Exceptional::{'df/arch/node/NotFound,NotFound'}(
                'Scaffold at ' . $this->context->location . ' cannot provide form delegate ' . $origName
            );
        }

        $output = $this->{$method}($state, $event, $id);

        if (!$output instanceof Delegate) {
            throw Exceptional::{'df/arch/node/NotFound,NotFound'}(
                'Scaffold at ' . $this->context->location . ' attempted but failed to provide form delegate ' . $origName
            );
        }

        return $output;
    }

    public function loadMenu(string $name, $id): Menu
    {
        $method = 'generate' . ucfirst($name) . 'Menu';

        if (!method_exists($this, $method)) {
            throw Exceptional::{'df/arch/navigation/NotFound,NotFound'}(
                'Scaffold at ' . $this->context->location . ' could not provider menu ' . $name
            );
        }

        return new ScaffoldMenu($this, $name, $id);
    }




    // Propagation
    public function getPropagatingQueryVars(): array
    {
        return (array)static::PROPAGATE_IN_QUERY;
    }

    protected function buildQueryPropagationInputs(array $filter = []): iterable
    {
        $output = [];
        $vars = array_merge(
            $this->getPropagatingQueryVars(),
            $this->request->query->getKeys()
        );


        foreach ($vars as $var) {
            if (in_array($var, $filter)) {
                continue;
            }

            if (isset($this->request->query->{$var})) {
                $output[] = $this->html->hidden($var, $this->request->query[$var]);
            }
        }

        return $output;
    }




    // Directory
    public function renderDirectoryTitle()
    {
        if (static::TITLE) {
            return $this->_(static::TITLE);
        }

        return Dictum::name($this->getDirectoryKeyName());
    }

    public function getDirectoryIcon(): ?string
    {
        if (static::ICON) {
            return static::ICON;
        }

        return $this->getDirectoryKeyName();
    }

    public function getDirectoryKeyName(): string
    {
        if ($this->directoryKeyName === null) {
            $parts = $this->context->location->getControllerParts();
            $this->directoryKeyName = array_pop($parts);
        }

        return $this->directoryKeyName;
    }


    // Helpers
    public function getNodeUri(string $node, array $query = null, $redirFrom = null, $redirTo = null, array $propagationFilter = []): DirectoryRequest
    {
        $output = clone $this->context->location;
        $output->setNode($node);
        $outQuery = $output->query;
        $propagate = $this->getPropagatingQueryVars();

        foreach ($outQuery->getKeys() as $key) {
            if (!in_array($key, $propagate)) {
                unset($outQuery->{$key});
            }
        }

        foreach ($propagate as $var) {
            if (!in_array($var, $propagationFilter) && isset($this->request->query->{$var})) {
                $outQuery->{$var} = $this->request->query[$var];
            }
        }

        foreach ($propagationFilter as $var) {
            unset($outQuery->{$var});
        }

        if ($query !== null) {
            $outQuery->import($query);
        }

        return $this->uri->directoryRequest($output, $redirFrom, $redirTo);
    }

    public function getIndexParentUri(): DirectoryRequest
    {
        return $this->uri->backRequest('../');
    }



    protected function generateNode(callable $callback): Node
    {
        return (new BaseNode($this->context, function ($node) use ($callback) {
            if (null !== ($pre = $this->onNodeDispatch($node))) {
                return $pre;
            }

            return $callback();
        }))
            ->setDefaultAccess($this->getDefaultAccess())
            ->setAccessSignifiers(...$this->getAccessSignifiers());
    }
}
