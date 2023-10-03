<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use df\arch;

use df\aura;
use df\core;

trait TWidget
{
    use core\lang\TChainable;

    protected $_primaryTag;
    protected $_context;

    public function setContext(arch\IContext $context)
    {
        $this->_context = $context;
        return $this;
    }

    public function getContext(): arch\IContext
    {
        return $this->_context;
    }


    public function getTag(): aura\html\ITag
    {
        if (!$this->_primaryTag) {
            $this->_primaryTag = new aura\html\Tag($this->_getPrimaryTagType());
            $this->_primaryTag->addClass('w');
        }

        return $this->_primaryTag;
    }

    protected function _getPrimaryTagType(): string
    {
        if (!static::PRIMARY_TAG) {
            throw Exceptional::Definition(
                'Primary tag name has not been declared for ' . $this->getWidgetName() . ' widget type'
            );
        }

        return static::PRIMARY_TAG;
    }

    public function isTagInline(): bool
    {
        return $this->getTag()->isInline();
    }

    public function isTagBlock(): bool
    {
        return $this->getTag()->isBlock();
    }

    public function toString(): string
    {
        return (string)$this->render();
    }
}


trait TWidget_RendererProvider
{
    protected $_renderer;

    public function setRenderer($renderer = null)
    {
        if ($renderer !== null) {
            $renderer = core\lang\Callback::factory($renderer);
        }

        $this->_renderer = $renderer;
        return $this;
    }

    public function getRenderer()
    {
        return $this->_renderer;
    }
}


trait TWidget_BodyContentAware
{
    protected $_body;

    public function setBody($body)
    {
        if (!$body instanceof aura\html\IElementContent) {
            $body = new aura\html\ElementContent($body, $this->getTag());
        }

        $this->_body = $body;
        return $this;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function hasBody()
    {
        if (!$this->_body) {
            return false;
        }

        if ($this->_body instanceof aura\html\ITag) {
            return true;
        }

        if ($this->_body instanceof aura\html\IElementContentCollection) {
            return !$this->_body->isEmpty();
        }

        return true;
    }
}



// Forms
trait TWidget_FormData
{
    //const PRIMARY_TAG = 'input';
    //const ARRAY_INPUT = false;

    protected $_name;
    protected $_value;
    protected $_targetFormId;

    protected function _hasArrayInput()
    {
        if ($this instanceof IOptionalMultipleValueInputWidget) {
            return $this->allowMultiple();
        }

        return static::ARRAY_INPUT;
    }

    protected function _applyFormDataAttributes(aura\html\ITag $tag, $includeValue = true)
    {
        // Name
        if ($this->_name == null) {
            return;
        }

        $name = $this->_name;

        if ($this->_hasArrayInput() && substr($name, -2) != '[]') {
            $name .= '[]';
        }

        $tag->setAttribute('name', $name);


        // Value
        if ($includeValue) {
            $tag->setAttribute('value', $this->getValueString());
        }

        if ($this->_value->hasErrors()) {
            $this->getTag()->addClass('error');
        }


        // Target form
        if ($this->_targetFormId) {
            $tag->setAttribute('form', $this->_targetFormId);
        }
    }


    // Name
    public function setName(?string $name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->_name;
    }


    // Value
    public function setValue($value)
    {
        if (!$value instanceof core\collection\IInputTree) {
            if ($value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }

            if ($value !== null) {
                $value = (string)$value;
            }

            $value = new core\collection\InputTree($value);
        }

        $this->_normalizeValue($value);
        $this->_value = $value;
        return $this;
    }

    protected function _normalizeValue(core\collection\IInputTree $value)
    {
    }

    public function getValue()
    {
        if (!$this->_value) {
            $this->_value = new core\collection\InputTree();
        }

        return $this->_value;
    }

    public function getValueString()
    {
        return $this->getValue()->getStringValue();
    }

    public function replaceValue($value)
    {
        $this->getValue()->setValue($value);
        return $this;
    }

    public function getErrors(): array
    {
        return $this->getValue()->getErrors();
    }


    // Target form
    public function setTargetFormId($id)
    {
        $this->_targetFormId = $id;
        return $this;
    }

    public function getTargetFormId()
    {
        return $this->_targetFormId;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*name' => $this->_name,
            '*value' => $this->_value,
            '%tag' => $this->getTag()
        ];
    }
}


trait TWidget_Input
{
    use core\constraint\TDisableable;
    use core\constraint\TRequirable;
    use core\constraint\TReadOnly;

    protected $_tabIndex;

    protected function _applyInputAttributes(aura\html\ITag $tag)
    {
        $tag->addClass('input');

        // Required
        if ($this->_isRequired) {
            $tag->setAttribute('required', 'required');
            //$tag->addClass('required');
        }


        // Disabled
        if ($this->_isDisabled) {
            $tag->setAttribute('disabled', 'disabled');
            //$tag->addClass('disabled');
        }


        // Read only
        if ($this->_isReadOnly) {
            $tag->setAttribute('readonly', 'readonly');
            //$tag->addClass('readOnly');
        }


        // Tab index
        if (is_numeric($this->_tabIndex)) {
            $tag->setAttribute('tabindex', (int)$this->_tabIndex);
        } elseif ($this->_tabIndex === false) {
            $tag->setAttribute('tabindex', '-1');
        }
    }


    public function setTabIndex($index)
    {
        $this->_tabIndex = $index;
        return $this;
    }

    public function getTabIndex()
    {
        return $this->_tabIndex;
    }
}


trait TWidget_TargetAware
{
    protected $_target;

    public function setTarget($target)
    {
        $this->_target = $target;
        return $this;
    }

    public function getTarget()
    {
        return $this->_target;
    }

    protected function _applyTargetAwareAttributes(aura\html\ITag $tag)
    {
        if ($this->_target !== null) {
            $tag->setAttribute('target', $this->_target);
        }
    }
}


trait TWidget_FocusableInput
{
    protected $_shouldAutoFocus = false;

    public function shouldAutoFocus(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldAutoFocus = $flag;
            return $this;
        }

        return $this->_shouldAutoFocus;
    }

    protected function _applyFocusableInputAttributes(aura\html\ITag $tag)
    {
        if ($this->_shouldAutoFocus) {
            $tag->setAttribute('autofocus', 'autofocus');
        }
    }
}


trait TWidget_VisualInput
{
    protected $_shouldValidate = true;
    protected $_shouldAutoComplete = null;

    public function shouldValidate(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldValidate = $flag;
            return $this;
        }

        return $this->_shouldValidate;
    }

    public function shouldAutoComplete(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldAutoComplete = $flag;
            return $this;
        }

        return (bool)$this->_shouldAutoComplete;
    }


    protected function _applyVisualInputAttributes(aura\html\ITag $tag)
    {
        if (!$this->_shouldValidate) {
            $tag->setAttribute('novalidate', true);
        }

        if ($this->_shouldAutoComplete !== null) {
            if ($this->_shouldAutoComplete) {
                $value = 'on';
            } else {
                $value = 'off';
            }

            $tag->setAttribute('autocomplete', $value);
        }
    }
}



trait TWidget_OptionalMultipleValueInput
{
    protected $_allowMultiple = false;

    public function allowMultiple(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_allowMultiple = $flag;
            return $this;
        }

        return $this->_allowMultiple;
    }

    protected function _applyOptionalMultipleValueInputAttributes(aura\html\ITag $tag)
    {
        if ($this->_allowMultiple) {
            $tag->setAttribute('multiple', 'multiple');
        }
    }
}


trait TWidget_DataListEntry
{
    protected $_dataListId;

    public function setDataListId($id)
    {
        $this->_dataListId = $id;
        return $this;
    }

    public function getDataListId()
    {
        return $this->_dataListId;
    }

    protected function _applyDataListEntryAttributes(aura\html\ITag $tag)
    {
        if ($this->_dataListId !== null) {
            $tag->setAttribute('list', $this->_dataListId);
        }
    }
}


trait TWidget_PlaceholderProvider
{
    protected $_placeholder;

    public function setPlaceholder($placeholder)
    {
        $this->_placeholder = $placeholder;
        return $this;
    }

    public function getPlaceholder()
    {
        return $this->_placeholder;
    }

    protected function _applyPlaceholderAttributes(aura\html\ITag $tag)
    {
        if ($this->_placeholder !== null) {
            $tag->setAttribute('placeholder', $this->_placeholder);
        }
    }
}


trait TWidget_TextEntry
{
    protected $_maxLength;
    protected $_spellCheck = null;

    public function setMaxLength(?int $length)
    {
        $this->_maxLength = $length;
        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->_maxLength;
    }

    public function shouldSpellCheck(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_spellCheck = $flag;
            return $this;
        }

        return $this->_spellCheck;
    }

    protected function _applyTextEntryAttributes(aura\html\ITag $tag)
    {
        if ($this->_maxLength !== null) {
            $tag->setAttribute('maxlength', (int)$this->_maxLength);
        }

        if ($this->_placeholder !== null) {
            $tag->setAttribute('placeholder', $this->_placeholder);
        }

        if ($this->_spellCheck !== null) {
            $tag->setAttribute('spellcheck', (bool)$this->_spellCheck);
        }
    }
}


trait TWidget_RangeEntry
{
    protected $_min;
    protected $_max;
    protected $_step;

    public function setRange($min, $max, $step = null)
    {
        $this->setMin($min)->setMax($max);

        if ($step !== null) {
            $this->setStep($step);
        }

        return $this;
    }

    // Min
    public function setMin($min)
    {
        $this->_min = $min;
        return $this;
    }

    public function getMin()
    {
        return $this->_min;
    }


    // Max
    public function setMax($max)
    {
        $this->_max = $max;
        return $this;
    }

    public function getMax()
    {
        return $this->_max;
    }


    // Step
    public function setStep($step)
    {
        $this->_step = $step;
        return $this;
    }

    public function getStep()
    {
        return $this->_step;
    }


    protected function _applyRangeEntryAttributes(aura\html\ITag $tag)
    {
        if ($this->_min !== null) {
            $tag->setAttribute('min', $this->_min);
        }

        if ($this->_max !== null) {
            $tag->setAttribute('max', $this->_max);
        }

        if ($this->_step !== null) {
            $tag->setAttribute('step', $this->_step);
        }
    }
}


trait TWidget_CheckInput
{
    protected $_isChecked = false;

    public function isChecked($flag = null)
    {
        if ($flag !== null) {
            if ($flag instanceof core\collection\IInputTree) {
                $this->getValue()->addErrors($flag->getErrors());
            }

            if ($flag instanceof core\IValueContainer) {
                $flag = $flag->getValue() == $this->getValueString();
            }

            $this->_isChecked = $flag;
            return $this;
        }

        return $this->_isChecked;
    }

    protected function _applyCheckInputAttributes(aura\html\ITag $tag)
    {
        if ($this->_isChecked) {
            $tag->setAttribute('checked', 'checked');
        }
    }
}


trait TWidget_SelectionInput
{
    protected $_optionRenderer;

    public function setOptionRenderer($renderer)
    {
        if ($renderer !== null) {
            $renderer = core\lang\Callback::factory($renderer);
        }

        $this->_optionRenderer = $renderer;
        return $this;
    }

    public function getOptionRenderer()
    {
        return $this->_optionRenderer;
    }

    protected function _renderOption(aura\html\IElement $option, $value, $label)
    {
        if ($this->_optionRenderer) {
            $this->_optionRenderer->invoke($option, $value, $label);
        } else {
            $option->push($label);
        }
    }
}


trait TWidget_UngroupedSelectionInput
{
    use TWidget_SelectionInput;

    protected $_options = [];

    public function setOptions($options, $labelsAsValues = false)
    {
        $this->_options = [];
        return $this->addOptions($options, $labelsAsValues);
    }

    public function addOptions($options, $labelsAsValues = false)
    {
        if ($options instanceof core\collection\ICollection) {
            $options = $options->toArray();
        }

        if (is_array($options)) {
            foreach ($options as $value => $label) {
                if ($labelsAsValues) {
                    $value = $label;
                }

                $this->_options[$value] = $label;
            }
        }

        return $this;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function sortOptions($byLabel = false)
    {
        if ($byLabel) {
            asort($this->_options);
        } else {
            ksort($this->_options);
        }

        return $this;
    }
}


trait TWidget_GroupedSelectionInput
{
    use TWidget_SelectionInput;

    protected $_groupOptions = [];
    protected $_groupNames = [];

    // Options
    public function setOptions($options, $labelsAsValues = false)
    {
        $this->_groupOptions = [];
        return $this->addOptions($options, $labelsAsValues);
    }

    public function addOptions($options, $labelsAsValues = false)
    {
        foreach (core\collection\Util::ensureIterable($options) as $key => $set) {
            $this->addGroupOptions($key, $set, $labelsAsValues);
            $label = $key;

            if (preg_match('/^[a-z][a-zA-Z0-9]+$/', (string)$key)) {
                $label = Dictum::label($key);
            }

            $this->setGroupName($key, $label);
        }

        return $this;
    }

    public function getOptions()
    {
        return $this->_groupOptions;
    }

    public function sortOptions($byLabel = false)
    {
        foreach ($this->_groupOptions as $key => $set) {
            $this->sortGroupOptions($key);
        }

        return $this;
    }


    // Group Options
    public function setGroupOptions($groupId, $options, $labelsAsValues = false)
    {
        unset($this->_groupOptions[$groupId]);
        return $this->addGroupOptions($groupId, $options, $labelsAsValues);
    }

    public function addGroupOptions($groupId, $options, $labelsAsValues = false)
    {
        if (!isset($this->_groupOptions[$groupId])) {
            $this->_groupOptions[$groupId] = [];
            $this->_groupNames[$groupId] = $groupId;
        }

        foreach (core\collection\Util::ensureIterable($options) as $value => $label) {
            if ($labelsAsValues) {
                $value = $label;
            }

            $this->_groupOptions[$groupId][$value] = $label;
        }

        return $this;
    }

    public function getGroupOptions($groupId)
    {
        if (!isset($this->_groupOptions[$groupId])) {
            return [];
        }

        return $this->_groupOptions[$groupId];
    }

    public function sortGroupOptions($groupId, $byLabel = false)
    {
        if (isset($this->_groupOptions[$groupId])) {
            if ($byLabel) {
                asort($this->_groupOptions[$groupId]);
            } else {
                ksort($this->_groupOptions[$groupId]);
            }
        }

        return $this;
    }


    // Groups
    public function addGroup($id, $name, $options = null, $labelsAsValues = false)
    {
        $this->setGroupOptions($id, $options, $labelsAsValues);
        $this->setGroupName($id, $name);

        return $this;
    }

    public function getGroup($id)
    {
        if (isset($this->_groupOptions[$id])) {
            return $this->_groupOptions[$id];
        }

        return [];
    }

    public function removeGroup($id)
    {
        unset($this->_groupOptions[$id], $this->_groupNames[$id]);
        return $this;
    }

    public function getGroups()
    {
        return $this->_groupOptions;
    }

    public function setGroupName($id, $name)
    {
        $this->_groupNames[$id] = $name;
        return $this;
    }

    public function getGroupName($id)
    {
        if (isset($this->_groupNames[$id])) {
            return $this->_groupNames[$id];
        }

        return $id;
    }
}



trait TWidget_NavigationEntryController
{
    protected $_entries;
    protected $_renderIfEmpty = false;
    protected $_showDescriptions = true;

    public function shouldShowDescriptions(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_showDescriptions = $flag;
            return $this;
        }

        return $this->_showDescriptions;
    }

    public function setEntries(...$entries)
    {
        $this->_entries->clear();
        return $this->addEntries(...$entries);
    }

    public function addLinks(...$entries)
    {
        return $this->addEntries(...$entries);
    }

    public function addEntries(...$entries)
    {
        foreach ($entries as $entry) {
            $this->addEntry($entry);
        }

        return $this;
    }

    public function addEntry($entry)
    {
        if (is_callable($entry)) {
            $entry = $entry($this);
        }

        if ($entry instanceof \Generator) {
            return $this->addEntries(...array_values(iterator_to_array($entry)));
        }

        if ($entry instanceof arch\navigation\IEntryListGenerator) {
            $entry = $entry->generateEntries();
        }

        if ($entry instanceof arch\navigation\IEntryList) {
            return $this->addEntries(...$entry->toArray());
        } elseif (is_array($entry)) {
            return $this->addEntries(...$entry);
        }


        if ($entry instanceof IWidgetProxy) {
            $entry = $entry->toWidget();
        }


        if ($entry instanceof arch\navigation\entry\None) {
            return $this;
        } elseif ($entry instanceof ILinkWidget
        || $entry instanceof arch\navigation\entry\Link) {
            $this->addLink($entry);
        } elseif ($entry instanceof self
        || $entry instanceof arch\navigation\entry\Menu) {
            $this->addMenu($entry);
        } elseif ($entry instanceof arch\navigation\entry\Spacer
        || (is_string($entry) && strlen($entry) == 1)) {
            $this->addSpacer();
        } elseif (is_string($entry) || $entry instanceof core\uri\IUrl) {
            try {
                $entry = arch\navigation\menu\Base::factory($this->_context, $entry);
                return $this->addEntries($entry);
            } catch (arch\navigation\SourceNotFoundException $e) {
            }
        }

        return $this;
    }

    public function addLink($link)
    {
        if (!$link instanceof ILinkWidget) {
            $link = Base::factory($this->_context, static::DEFAULT_LINK_WIDGET, func_get_args());
        }

        if (static::ENFORCE_DEFAULT_LINK_WIDGET) {
            $class = 'df\\aura\\html\\widget\\' . static::DEFAULT_LINK_WIDGET;

            if (!$link instanceof $class) {
                throw Exceptional::InvalidArgument(
                    'Links in ' . $this->getWidgetName() . ' widgets must be of type ' . static::DEFAULT_LINK_WIDGET
                );
            }
        }

        $this->_entries->push($link);
        return $this;
    }

    public function addMenu($menu)
    {
        if (!$menu instanceof self) {
            $menu = Base::factory($this->_context, 'Menu', func_get_args());
        }

        $this->_entries->push($menu);
        return $this;
    }

    public function addSpacer()
    {
        if ($this->_entries->getLast() instanceof ILinkWidget) {
            $this->_entries->push(new aura\html\ElementString('<span class="w spacer"></span>'));
        }

        return $this;
    }

    public function getEntries()
    {
        return $this->_entries;
    }

    public function removeEntry($index)
    {
        $this->_entries->remove($index);
        return $this;
    }

    public function clearEntries()
    {
        $this->_entries->clear();
        return $this;
    }


    public function shouldRenderIfEmpty(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_renderIfEmpty = $flag;
            return $this;
        }

        return $this->_renderIfEmpty;
    }
}


trait TWidget_DispositionAware
{
    protected $_disposition = null;

    public function setDisposition($disposition)
    {
        if ($disposition === null) {
            $this->_disposition = null;
            return $this;
        }

        if (is_bool($disposition)) {
            if ($disposition) {
                $disposition = 'positive';
            } else {
                $disposition = 'negative';
            }
        }

        $disposition = strtolower((string)$disposition);

        switch ($disposition) {
            case 'positive':
            case 'negative':
            case 'informative':
            case 'operative':
            case 'transitive':
            case 'external':
                break;

            case 'neutral':
            default:
                $disposition = 'neutral';
                break;
        }

        $this->_disposition = $disposition;
        return $this;
    }

    public function getDisposition()
    {
        if ($this->_disposition == 'neutral') {
            return null;
        }

        return $this->_disposition;
    }
}



trait TWidget_IconProvider
{
    protected $_icon;

    public function setIcon(string $icon = null)
    {
        $this->_icon = $icon;

        if ($this instanceof IDispositionAwareWidget && $this->_disposition === null) {
            switch ($icon) {
                // positive
                case 'add':
                case 'save':
                case 'accept':
                    $this->setDisposition('positive');
                    break;

                    // negative
                case 'remove':
                case 'delete':
                case 'deny':
                    $this->setDisposition('negative');
                    break;

                    // informative
                case 'info':
                case 'refresh':
                    $this->setDisposition('informative');
                    break;

                    // operative
                case 'edit':
                    $this->setDisposition('operative');
                    break;

                    // transitive
                case 'back':
                case 'cancel':
                case 'preview':
                    $this->setDisposition('transitive');
                    break;

                    // external
                case 'link':
                    //$this->setDisposition('external');
                    break;
            }
        }

        return $this;
    }

    public function getIcon()
    {
        return $this->_icon;
    }

    protected function _generateIcon()
    {
        if ($this->_icon) {
            return $this->_context->html->icon($this->_icon);
        }
    }
}


// Lists
trait TWidget_DataDrivenList
{
    protected $_data;

    public function setData($data)
    {
        if ($data instanceof core\IArrayProvider
        && !$data instanceof core\collection\IMappedCollection) {
            $data = $data->toArray();
        }

        $this->_data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->_data;
    }

    protected function _isDataIterable()
    {
        return is_array($this->_data)
            || $this->_data instanceof \Iterator
            || $this->_data instanceof \IteratorAggregate;
    }
}


trait TWidget_OrderedDataDrivenList
{
    protected $_start;
    protected $_isReversed = false;

    public function setStartIndex($start)
    {
        if (empty($start) && $start != 0) {
            $start = null;
        } else {
            $start = (int)$start;
        }

        $this->_start = $start;
        return $this;
    }

    public function getStartIndex()
    {
        return $this->_startIndex;
    }

    public function isReversed(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isReversed = $flag;
            return $this;
        }

        return $this->_isReversed;
    }

    public function setData($data)
    {
        if ($data instanceof core\collection\IPageable) {
            $paginator = $data->getPaginator();

            if ($paginator) {
                $this->setStartIndex($paginator->getOffset());
            }
        }

        $this->_data = $data;
        return $this;
    }
}

trait TWidget_RendererContextProvider
{
    protected $_rendererContext;

    public function getRendererContext()
    {
        if (!$this->_rendererContext) {
            $this->_rendererContext = new aura\html\widget\util\RendererContext($this);
        }

        return $this->_rendererContext;
    }
}

trait TWidget_MappedList
{
    protected $_rowProcessor;
    protected $_fields = [];

    public function setRowProcessor($processor = null)
    {
        if ($processor !== null) {
            $processor = core\lang\Callback::factory($processor);
        }

        $this->_rowProcessor = $processor;
        return $this;
    }

    public function getRowProcessor()
    {
        return $this->_rowProcessor;
    }

    public function setField(IField $field)
    {
        $this->_fields[$field->getKey()] = $field;
        return $this;
    }

    public function addField($key, $a = null, $b = null)
    {
        return $this->addFieldAtIndex(null, $key, $a, $b);
    }

    public function addFieldAtIndex($index, $key, $a = null, $b = null)
    {
        $name = null;
        $renderer = null;

        if (is_callable($a) && !is_string($a)) {
            $renderer = $a;
            $name = $b;
        } elseif (is_callable($b) && !is_string($b)) {
            $renderer = $b;
            $name = $a;
        } elseif (is_string($a) && $b === null) {
            $name = $a;
        }

        if ($name === null) {
            $name = Dictum::label($key);
        }

        if ($renderer === null) {
            $renderer = function ($data, $renderContext) {
                $key = $renderContext->getField();
                $value = null;

                if (is_array($data)) {
                    if (isset($data[$key])) {
                        $value = $data[$key];
                    } else {
                        $value = null;
                    }
                } elseif ($data instanceof \ArrayAccess) {
                    $value = $data[$key];
                } elseif (is_object($data)) {
                    if (method_exists($data, '__get')) {
                        $value = $data->__get($key);
                    } elseif (method_exists($data, 'get' . ucfirst($key))) {
                        $value = $data->{'get' . ucfirst($key)}();
                    }
                }

                return $value;
            };
        }

        $field = new aura\html\widget\util\Field($key, $name, $renderer);

        if ($index === null) {
            $this->_fields[$key] = $field;
        } else {
            $index = (int)$index;
            $count = count($this->_fields);

            if ($index < 0) {
                $index += $count;

                if ($index < 0) {
                    $index = 0;
                }
            }

            if ($index > $count) {
                $index = $count;
            }

            $fields = [];
            $added = false;
            $i = 0;

            foreach ($this->_fields as $tKey => $tField) {
                if ($index == $i) {
                    $fields[$key] = $field;
                    $added = true;
                }

                $fields[$tKey] = $tField;
                $i++;
            }

            if (!$added) {
                $fields[$key] = $field;
            }

            $this->_fields = $fields;
        }

        return $this;
    }

    public function removeField($key)
    {
        if ($key instanceof IField) {
            $key = $key->getKey();
        }

        unset($this->_fields[$key]);

        return $this;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function getField($key)
    {
        if (isset($this->_fields[$key])) {
            return $this->_fields[$key];
        }
    }

    public function hasFields()
    {
        return !empty($this->_fields);
    }

    protected function _generateDefaultFields()
    {
        foreach ($this->_data as $key => $value) {
            $this->addField($key);
        }

        $fields = $this->_fields;
        $this->_fields = [];

        return $fields;
    }

    public function addLabel($fieldKey, $labelKey, $label = null)
    {
        if (!isset($this->_fields[$fieldKey])) {
            throw Exceptional::NotFound(
                'Field ' . $fieldKey . ' not found'
            );
        }

        $this->_fields[$fieldKey]->addLabel($labelKey, $label);
        return $this;
    }

    public function removeLabel($fieldKey, $labelKey)
    {
        if (isset($this->_fields[$fieldKey])) {
            $this->_fields[$fieldKey]->removeLabel($labelKey);
        }

        return $this;
    }
}
