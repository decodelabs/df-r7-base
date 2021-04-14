<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component;

use df;
use df\core;
use df\arch;
use df\aura;

use DecodeLabs\Tagged as Html;
use DecodeLabs\Dictum;

use DecodeLabs\Glitch\Dumpable;

class AttributeList extends Base implements aura\html\widget\IWidgetProxy, Dumpable
{
    protected $record;
    protected $renderIfEmpty = null;
    protected $fields = [];
    protected $viewArg;

    protected function init(array $fields=null, $record=null)
    {
        if ($record) {
            $this->setRecord($record);
        }

        if (!empty($fields)) {
            $this->setFields($fields);
        }

        if ($this->viewArg === null) {
            $this->viewArg = 'record';
        }
    }

    // Record
    public function setRecord($record)
    {
        $this->record = $record;
        return $this;
    }

    public function getRecord()
    {
        return $this->record;
    }

    // Error
    public function shouldRenderIfEmpty(bool $flag=null)
    {
        if ($flag !== null) {
            $this->renderIfEmpty = $flag;
            return $this;
        }

        return $this->renderIfEmpty;
    }

    // Fields
    public function setFields(array $fields)
    {
        foreach ($fields as $key => $value) {
            $this->setField($key, $value);
        }

        return $this;
    }

    public function setField($key, $value)
    {
        if (is_string($value) && is_int($key)) {
            $key = $value;
            $value = true;
        }

        if ($key == '--') {
            $key = uniqid('--');
            $value = '';
        }

        if (
            $value === true &&
            isset($this->fields[$key]) &&
            $this->fields[$key] instanceof core\lang\ICallback
        ) {
            return $this;
        }

        if (is_callable($value)) {
            $value = core\lang\Callback::factory($value);
        }

        $this->fields[$key] = $value;
        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function hideField(...$keys)
    {
        foreach ($keys as $key) {
            if (isset($this->fields[$key])) {
                $this->fields[$key] = false;
            }
        }

        return $this;
    }

    public function showField(...$keys)
    {
        foreach ($keys as $key) {
            if (isset($this->fields[$key]) && $this->fields[$key] == false) {
                $this->fields[$key] = true;
            }
        }

        return $this;
    }

    public function isFieldVisible($key): bool
    {
        return isset($this->fields[$key])
            && $this->fields[$key] !== false;
    }

    public function addCustomField($key, $callback)
    {
        $this->fields[$key] = core\lang\Callback::factory($callback);
        return $this;
    }

    // View arg
    public function setViewArg($arg)
    {
        $this->viewArg = $arg;
        return $this;
    }

    public function getViewArg()
    {
        return $this->viewArg;
    }


    // Render
    public function toWidget(): ?aura\html\widget\IWidget
    {
        return $this->render();
    }

    protected function _execute()
    {
        if ($this->record === null
        && $this->viewArg !== null
        && $this->view->hasSlot($this->viewArg)) {
            $this->record = $this->view->getSlot($this->viewArg);
        }

        $output = [];
        $list = $this->_createBaseList();
        $divider = 0;

        foreach ($this->fields as $key => $value) {
            if (substr($key, 0, 2) == '--') {
                if (is_string($value)) {
                    $title = $value;
                } elseif ($key != '--') {
                    $title = Dictum::name(substr($key, 2));
                } else {
                    $title = null;
                }

                $list->addField('divider'.($divider++), function ($data, $context) use ($title) {
                    if ($title) {
                        $context->setDivider($title);
                    } else {
                        $context->addDivider();
                    }

                    $context->skipRow();
                });

                continue;
            }

            if ($value === true) {
                $func = 'add'.ucfirst($key).'Field';

                if (method_exists($this, $func)) {
                    $this->{$func}($list);
                } else {
                    $list->addField($key);
                }
            } elseif (is_callable($value)) {
                core\lang\Callback::call($value, $list, $key);
            }
        }

        if ($list->hasFields()) {
            $output[] = $list;
        }

        if (count($output) < 2) {
            $output = array_pop($output);
        } else {
            $output = Html::{'div'}($output);
        }

        return $output;
    }

    protected function _createBaseList()
    {
        $output = $this->view->html->attributeList($this->record);
        $output->getRendererContext()->setComponent($this);

        if ($this->renderIfEmpty !== null) {
            $output->shouldRenderIfEmpty($this->renderIfEmpty);
        }

        return $output;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->render();
    }
}
