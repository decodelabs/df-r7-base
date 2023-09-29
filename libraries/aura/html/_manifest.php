<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html;

use DecodeLabs\Elementary\Tag as TagInterface;
use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Markup;
use DecodeLabs\Tagged\Tag;

use df\aura;
use df\core;

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
    public function getDataAttribute($key, $default = null);
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
    public function isHidden(bool $flag = null);
    public function setTitle(?string $title);
    public function getTitle(): ?string;


    // Style
    public function setStyles(...$styles);
    public function addStyles(...$styles);
    public function getStyles();
    public function setStyle($key, $value);
    public function getStyle($key, $default = null);
    public function removeStyle(...$keys);
    public function hasStyle(...$keys);
}



interface ITag extends IElementRepresentation, \ArrayAccess, ITagDataContainer, core\lang\IChainable
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
    public function renderWith($innerContent = null, $expanded = false);
    public function shouldRenderIfEmpty(bool $flag = null);
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

    private static function flattenArray($data, bool $removeNull = false)
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
                $stringValue = $stringValue . "\n";
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
                throw Exceptional::Runtime(
                    'Unable to get view target for rendering'
                );
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
            $output = Html::esc($output);
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


class ElementContent implements IElementContentCollection, Dumpable
{
    use TElementContent;

    public static function normalize($content, $parent = null)
    {
        return new aura\html\ElementString(
            (new self($content, $parent))->toString()
        );
    }

    public function __construct($content = null, $parent = null)
    {
        $this->setParentRenderContext($parent);

        if ($content !== null) {
            $this->import($content);
        }
    }
}


class ElementString implements IElementRepresentation, Dumpable
{
    protected $_content = '';

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
        $this->_content = $str . $this->_content;
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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => (string)$this->_content;
    }
}


interface IElement extends ITag, IElementContentCollection
{
    public function setBody($body);
}


/**
 * @method Markup add(string $tag, $body, array $attr=[])
 * @method widget\Address addAddress($address=null)
 * @method widget\AttributeList addAttributeList($data=null, $renderer=null)
 * @method widget\BlockLink addBlockLink($uri, $body=null, $description=null, $matchRequest=null)
 * @method widget\BlockMenu addBlockMenu(...$entries)
 * @method widget\BreadcrumbList addBreadcrumbList(...$entries)
 * @method widget\Button addButton($name, $body=null, $value=null)
 * @method widget\ButtonArea addButtonArea(...$input)
 * @method widget\ButtonGroup addButtonGroup(...$input)
 * @method widget\Checkbox addCheckbox($name, $isChecked=false, $body=null, $value='1')
 * @method widget\CheckboxGroup addCheckboxGroup($name, $value=null, $options=null, $labelsAsValues=false)
 * @method widget\CheckboxList addCheckboxList(core\collection\IInputTree $values, array $options)
 * @method widget\CollectionList addCollectionList($data, core\collection\IPaginator $paginator=null)
 * @method widget\CollectionStack addCollectionStack($data)
 * @method widget\ColorPicker addColorPicker($name, $value=null)
 * @method widget\Container addContainer(...$input)
 * @method widget\Currency addCurrency($name, $value=null, string $inputCurrency=null, bool $allowSelection=false, int $precision=2)
 * @method widget\DataList addDataList($id, $options=null)
 * @method widget\DatePicker addDatePicker($name, $value=null, $outputFormat=null)
 * @method widget\DateTimePicker addDateTimePicker($name, $value=null, $timezone=null)
 * @method widget\ButtonArea addDefaultButtonGroup($mainEvent=null, $mainEventText=null, $mainEventIcon=null)
 * @method widget\Duration addDuration($name, $value=null)
 * @method widget\EmailTextbox addEmailTextbox($name, $value=null)
 * @method widget\EventButton addEventButton($event, $body=null)
 * @method widget\Field addField($body=null, $input=null)
 * @method widget\FieldError addFieldError($errors=null)
 * @method widget\FieldSet addFieldSet($legend=null)
 * @method widget\FileUpload addFileUpload($name, $value=null)
 * @method widget\FlashMessage addFlashMessage($message, $type=null)
 * @method widget\Form addForm($action=null, $method=null, $encoding=null)
 * @method widget\GroupedSelect addGroupedSelect($name, $value=null, $options=null, $labelsAsValues=false)
 * @method widget\GroupedMultiSelect addGroupedMultiSelect($name, $value=null, $options=null, $labelsAsValues=false)
 * @method widget\Hidden addHidden($name, $value=null)
 * @method widget\Label addLabel($body, $inputId=null)
 * @method widget\Link addLink($uri, $body=null, $matchRequest=null)
 * @method widget\Menu addMenu(...$entries)
 * @method widget\MenuBar addMenuBar(...$entries)
 * @method widget\MonthPicker addMonthPicker($name, $value=null, $outputFormat=null)
 * @method widget\MultiSelect addMultiSelect($name, $value=null, $options=null, $labelsAsValues=false)
 * @method widget\NumberTextbox addNumberTextbox($name, $value=null)
 * @method widget\OrderList addOrderList($data)
 * @method widget\Overlay addOverlay($title=null, $url=null)
 * @method widget\Paginator addPaginator($data)
 * @method widget\PanelSet addPanelSet()
 * @method widget\PasswordTextbox addPasswordTextbox($name, $value=null)
 * @method widget\PrioritySlider addPrioritySlider($name, $value=null)
 * @method widget\ProgressBar addProgressBar($value, $max=100, $min=0)
 * @method widget\Radio addRadio($name, $isChecked=false, $body=null, $value='1')
 * @method widget\RadioGroup addRadioGroup($name, $value=null, $options=null, $labelsAsValues=false)
 * @method widget\RangeSlider addRangeSlider($name, $value=null)
 * @method widget\Recaptcha addRecaptcha($siteKey=null)
 * @method widget\ResetButton addResetButton($name, $body=null, $value=null)
 * @method widget\SearchTextbox addSearchTextbox($name, $value=null)
 * @method widget\Select addSelect($name, $value=null, $options=null, $labelsAsValues=false)
 * @method widget\StarRating addStarRating($value, $max=5)
 * @method widget\SubmitButton addSubmitButton($name, $body=null, $value=null)
 * @method widget\TelephoneTextbox addTelephoneTextbox($name, $value=null)
 * @method widget\TextArea addTextArea($name, $value=null)
 * @method widget\Textbox addTextbox($name, $value=null)
 * @method widget\TimePicker addTimePicker($name, $value=null, $outputFormat=null)
 * @method widget\UrlTextbox addUrlTextbox($name, $value=null)
 * @method widget\WeekPicker addWeekPicker($name, $value=null, $outputFormat=null)
 */
interface IMarkupAdder
{
}
