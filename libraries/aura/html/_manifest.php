<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;
use df\flex;

use DecodeLabs\Tagged\Markup;
use DecodeLabs\Tagged\Builder\Tag as TagInterface;
use DecodeLabs\Tagged\Html\Tag;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

interface IRenderable
{
    public function render();
}

interface IElementRepresentation extends core\IStringProvider, IRenderable, Markup
{
}


interface ITagDataContainer extends core\collection\IAttributeContainer
{
    // Data attributes
    public function setDataAttributes(array $attributes);
    public function setDataAttribute($key, $value);
    public function getDataAttribute($key, $default=null);
    public function hasDataAttribute($key);
    public function removeDataAttribute($key);
    public function getDataAttributes();

    // Class attributes
    public function setClasses(...$classes);
    public function addClasses(...$classes);
    public function getClasses();
    public function setClass(...$classes);
    public function addClass(...$classes);
    public function removeClass(...$classes);
    public function hasClass(...$classes);
    public function countClasses();

    // Direct attributes
    public function setId(?string $id);
    public function getId(): ?string;
    public function isHidden(bool $flag=null);
    public function setTitle(?string $title);
    public function getTitle(): ?string;


    // Style
    public function setStyles(...$styles);
    public function addStyles(...$styles);
    public function getStyles();
    public function setStyle($key, $value);
    public function getStyle($key, $default=null);
    public function removeStyle(...$keys);
    public function hasStyle(...$keys);
}



interface ITag extends IElementRepresentation, \ArrayAccess, ITagDataContainer, flex\IStringEscapeHandler, core\lang\IChainable
{
    // Name
    public function setName($name);
    public function getName(): string;
    public function isInline(): bool;
    public function isBlock(): bool;

    // Render count
    public function getRenderCount();

    // Strings
    public function open();
    public function close();
    public function renderWith($innerContent=null, $expanded=false);
    public function shouldRenderIfEmpty(bool $flag=null);
}


interface IWidgetFinder
{
    public function getFirstWidgetOfType($type);
    public function getAllWidgetsOfType($type);
    public function findFirstWidgetOfType($type);
    public function findAllWidgetsOfType($type);
}




interface IElementContent extends IElementRepresentation, core\lang\IChainable
{
    public function setParentRenderContext($parent);
    public function getParentRenderContext();
    public function getElementContentString();
    public function esc($value): string;
}

interface IElementContentCollection extends
    IElementContent,
    IWidgetFinder,
    core\collection\IIndexedQueue,
    \IteratorAggregate
{
}

trait TElementContent
{
    use core\TStringProvider;
    use core\lang\TChainable;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_Constructor;
    use core\collection\TArrayCollection_ProcessedIndexedValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_Sliceable;
    use core\collection\TArrayCollection_ProcessedShiftable;
    use core\collection\TArrayCollection_IndexedMovable;

    protected $_parent;

    public function import(...$input)
    {
        foreach (self::flattenArray($input, true) as $value) {
            $this->_collection[] = $value;
        }

        return $this;
    }

    private static function flattenArray($data, bool $removeNull=false)
    {
        if (!is_array($data)) {
            yield $data;
        }

        foreach ($data as $key => $value) {
            if ($isIterable = is_array($value)) {
                $outer = $value;
            } else {
                $outer = null;
            }

            if ($isContainer = $value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }

            if ((!$isIterable || $isContainer)
            && (!$removeNull || $value !== null)) {
                yield $key => $value;
            }

            if ($isIterable) {
                yield from self::flattenArray($outer, $removeNull);
            }
        }
    }

    public function setParentRenderContext($parent)
    {
        $this->_parent = $parent;
        return $this;
    }

    public function getParentRenderContext()
    {
        return $this->_parent;
    }

    public function toString(): string
    {
        return $this->getElementContentString();
    }

    public function getElementContentString()
    {
        $output = '';
        $lastElement = null;

        foreach ($this->_collection as $value) {
            if (empty($value) && $value != '0') {
                continue;
            }

            $stringValue = (string)$this->_renderChild($value);
            $isBlock = false;

            if ($value instanceof aura\html\widget\IWidget) {
                $isBlock = $value->isTagBlock();
                $stringValue = trim($stringValue);
            } elseif ($value instanceof ITag || $value instanceof TagInterface) {
                $isBlock = $value->isBlock();
            } elseif (preg_match('/\<\/?([a-zA-Z0-9]+)( |\>)/i', $stringValue, $matches)) {
                $isBlock = !Tag::isInlineTagName($matches[1]);
            }

            if ($isBlock) {
                $stringValue = $stringValue."\n";
            }

            $output .= $stringValue;
            continue;
        }

        return $output;
    }

    protected function _renderChild(&$value)
    {
        if ($value instanceof IRenderable) {
            $value = $value->render();
        }

        if (is_callable($value) && is_object($value)) {
            $value = $value($this->_parent ?? $this);
            return $this->_renderChild($value);
        }

        if (is_array($value) || $value instanceof \Generator) {
            $output = '';

            foreach ($value as $part) {
                $output .= $this->_renderChild($part);
            }

            return $output;
        }

        if ($value instanceof aura\html\widget\IWidgetProxy) {
            $value = $value->toWidget();
        }

        if ($value instanceof aura\view\IDeferredRenderable) {
            if ($this instanceof aura\view\IRenderTargetProvider) {
                $value->setRenderTarget($this->getRenderTarget());
            } elseif ($this->_parent instanceof aura\view\IRenderTargetProvider) {
                $value->setRenderTarget($this->_parent->getRenderTarget());
            } elseif ($this->_parent instanceof aura\view\IRenderTarget) {
                $value->setRenderTarget($this->_parent);
            }
        }

        $test = false;

        if ($value instanceof IRenderable) {
            $output = $value->render();
        } elseif ($value instanceof aura\view\IDeferredRenderable) {
            $value = $value->render();

            if (is_string($value)) {
                $value = new ElementString($value);
            }

            $output = $this->_renderChild($value);
        } elseif ($value instanceof aura\view\IRenderable) {
            if ($this instanceof aura\view\IRenderTargetProvider) {
                $value = $value->renderTo($this->getRenderTarget());
            } elseif ($this->_parent instanceof aura\view\IRenderTargetProvider) {
                $value = $value->renderTo($this->_parent->getRenderTarget());
            } elseif ($this->_parent instanceof aura\view\IRenderTarget) {
                $value = $value->renderTo($this->_parent);
            } else {
                throw Glitch::ERuntime('Unable to get view target for rendering');
            }

            if (is_string($value)) {
                $value = new ElementString($value);
            }

            $output = $value = $this->_renderChild($value);
        } else {
            $output = (string)$value;
        }

        if (!$value instanceof IElementRepresentation &&
            !$value instanceof Markup) {
            $output = $this->esc($output);
        }

        return $output;
    }

    protected function _expandInput($input): array
    {
        if (!is_array($input)) {
            $input = [$input];
        }

        foreach ($input as $i => $value) {
            if ($value instanceof aura\html\widget\IWidgetProxy) {
                $input[$i] = $value->toWidget();
            }
        }

        return $input;
    }

    public function render()
    {
        return new ElementString($this->toString());
    }



    public function getFirstWidgetOfType($type)
    {
        foreach ($this->_collection as $child) {
            if ($child instanceof aura\html\widget\IWidget && $child->getWidgetName() == $type) {
                return $child;
            }
        }

        return null;
    }

    public function getAllWidgetsOfType($type)
    {
        $output = [];

        foreach ($this->_collection as $child) {
            if ($child instanceof aura\html\widget\IWidget && $child->getWidgetName() == $type) {
                $output[] = $child;
            }
        }

        return $output;
    }

    public function findFirstWidgetOfType($type)
    {
        foreach ($this->_collection as $child) {
            if ($child instanceof aura\html\widget\IWidget
            && $child->getWidgetName() == $type) {
                return $child;
            }

            if ($child instanceof IWidgetFinder) {
                if ($ret = $child->findFirstWidgetOfType($type)) {
                    return $ret;
                }
            }
        }

        return null;
    }

    public function findAllWidgetsOfType($type)
    {
        $output = [];

        foreach ($this->_collection as $child) {
            if (!$child instanceof aura\html\widget\IWidget
            && $child->getWidgetName() == $type) {
                $output[] = $child;
            }

            if ($child instanceof IWidgetFinder) {
                $output = array_merge($output, $child->findAllWidgetsOfType($type));
            }
        }

        return $output;
    }
}


class ElementContent implements IElementContentCollection, Inspectable
{
    use TElementContent;
    use flex\THtmlStringEscapeHandler;

    public static function normalize($content, $parent=null)
    {
        return new aura\html\ElementString(
            (new self($content, $parent))->toString()
        );
    }

    public function __construct($content=null, $parent=null)
    {
        $this->setParentRenderContext($parent);

        if ($content !== null) {
            $this->import($content);
        }
    }
}


class ElementString implements IElementRepresentation, Inspectable
{
    protected $_content;

    public function __construct($content)
    {
        $this->_content = (string)$content;
    }

    public function __toString(): string
    {
        return $this->_content;
    }

    public function toString(): string
    {
        return $this->_content;
    }

    public function render()
    {
        return $this;
    }

    public function prepend($str)
    {
        $this->_content = $str.$this->_content;
        return $this;
    }

    public function append($str)
    {
        $this->_content .= $str;
        return $this;
    }

    public function isEmpty(): bool
    {
        return !strlen($this->_content);
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText((string)$this->_content);
    }
}


interface IElement extends ITag, IElementContentCollection
{
    public function setBody($body);
}
