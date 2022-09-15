<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Tagged as Html;

class Container extends Base implements
    IContainerWidget,
    IWidgetShortcutProvider,
    Dumpable
{
    use core\TValueMap;

    protected $_children;
    protected $_context;

    public function __construct(arch\IContext $context, ...$input)
    {
        parent::__construct($context);

        $this->_context = $context;
        $this->_children = new aura\html\ElementContent($input, $this->getTag());
    }

    protected function _render()
    {
        if ($this->_children->isEmpty()) {
            return '';
        }

        return $this->getTag()->renderWith(
            $this->_prepareChildren(), true
        );
    }

    protected function _prepareChildren($callback=null)
    {
        if ($callback !== null) {
            $callback = core\lang\Callback::factory($callback);
        }

        $children = $this->_children->toArray();
        $this->_children->clear();

        foreach ($children as $i => $child) {
            if ($child instanceof arch\node\ISelfContainedRenderableDelegate) {
                $child = $child->renderContainerContent($this);
            }

            if ($child !== null && $callback) {
                $child = $callback->invoke($child, $this);
            }

            if ($child !== null) {
                $this->_children->push($child);
            }
        }

        return $this->_children->render();
    }

    public function import(...$input)
    {
        $this->_children->import(...$input);
        return $this;
    }

    public function toArray(): array
    {
        return $this->_children->toArray();
    }

    public function isEmpty(): bool
    {
        return $this->_children->isEmpty();
    }

    public function clear()
    {
        $this->_children->clear();
        return $this;
    }

    public function set($index, $value)
    {
        $this->_children->set($index, $value);
        return $this;
    }

    public function put($index, $value)
    {
        $this->_children->put($index, $value);
        return $this;
    }

    public function move($key, $index)
    {
        $this->_children->move($key, $index);
        return $this;
    }

    public function get($index, $default=null)
    {
        return $this->_children->get($index, $default);
    }

    public function has(...$indexes)
    {
        return $this->_children->has(...$indexes);
    }

    public function remove(...$indexes)
    {
        $this->_children->remove(...$indexes);
        return $this;
    }

    public function getIndex($value)
    {
        return $this->_children->getIndex($value);
    }

    public function getNext()
    {
        return $this->_children->getNext();
    }

    public function getPrev()
    {
        return $this->_children->getPrev();
    }

    public function getFirst()
    {
        return $this->_children->getFirst();
    }

    public function getLast()
    {
        return $this->_children->getLast();
    }

    public function getCurrent()
    {
        return $this->_children->getCurrent();
    }

    public function seekFirst()
    {
        return $this->_children->seekFirst();
    }

    public function seekNext()
    {
        return $this->_children->seekNext();
    }

    public function seekPrev()
    {
        return $this->_children->seekPrev();
    }

    public function seekLast()
    {
        return $this->_children->seekLast();
    }

    public function hasSeekEnded()
    {
        return $this->_children->hasSeekEnded();
    }

    public function getSeekPosition()
    {
        return $this->_children->getSeekPosition();
    }

    public function extract()
    {
        return $this->_children->extract();
    }

    public function extractList(int $count): array
    {
        return $this->_children->extractList($count);
    }

    public function insert(...$values)
    {
        $this->_children->insert(...$values);
        return $this;
    }

    public function pop()
    {
        return $this->_children->pop();
    }

    public function push(...$values)
    {
        $this->_children->push(...$values);
        return $this;
    }

    public function shift()
    {
        return $this->_children->shift();
    }

    public function unshift(...$values)
    {
        $this->_children->unshift(...$values);
        return $this;
    }

    public function slice(int $offset, int $length=null): array
    {
        return $this->_children->slice($offset, $length=null);
    }

    public function getSlice(int $offset, int $length=null): array
    {
        return $this->_children->getSlice($offset, $length);
    }

    public function removeSlice(int $offset, int $length=null)
    {
        $this->_children->removeSlice($offset, $length);
        return $this;
    }

    public function keepSlice(int $offset, int $length=null)
    {
        $this->_children->keepSlice($offset, $length);
        return $this;
    }


    public function getFirstWidgetOfType($type)
    {
        return $this->_children->getFirstWidgetOfType($type);
    }

    public function getAllWidgetsOfType($type)
    {
        return $this->_children->getAllWidgetsOfType($type);
    }

    public function findFirstWidgetOfType($type)
    {
        return $this->_children->findFirstWidgetOfType($type);
    }

    public function findAllWidgetsOfType($type)
    {
        return $this->_children->findAllWidgetsOfType($type);
    }


    public function count(): int
    {
        return $this->_children->count();
    }

    public function getIterator()
    {
        return $this->_children->getIterator();
    }

    public function offsetSet($index, $value)
    {
        $this->_children->offsetSet($index, $value);
        return $this;
    }

    public function offsetGet($index)
    {
        return $this->_children->offsetGet($index);
    }

    public function offsetExists($index)
    {
        return $this->_children->offsetExists($index);
    }

    public function offsetUnset($index)
    {
        $this->_children->offsetUnset($index);
        return $this;
    }



    // Widget shortcuts
    public function __call($method, array $args)
    {
        $add = false;

        if (substr($method, 0, 3) == 'add') {
            $add = true;
            $method = lcfirst(substr($method, 3));
        }

        if (empty($method)) {
            $method = '__invoke';
        }

        $widget = $this->_context->html->{$method}(...$args);

        if ($add) {
            $this->push($widget);
        }

        return $widget;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:%tag' => $this->getTag();
        yield 'values' => $this->_children->toArray();
    }
}
