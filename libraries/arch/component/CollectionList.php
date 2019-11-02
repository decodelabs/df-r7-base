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

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class CollectionList extends Base implements aura\html\widget\IWidgetProxy, Inspectable
{
    const DEFAULT_ERROR_MESSAGE = null;

    protected $_collection;
    protected $_errorMessage;
    protected $_renderIfEmpty = null;
    protected $_fields = [];
    protected $_urlRedirect = null;
    protected $_viewArg;
    protected $_mode = 'get';
    protected $_postEvent = 'paginate';

    protected function init(array $fields=null, $collection=null)
    {
        if (static::DEFAULT_ERROR_MESSAGE !== null) {
            $this->_errorMessage = $this->_(static::DEFAULT_ERROR_MESSAGE);
        }

        if ($collection) {
            $this->setCollection($collection);
        }

        if (!empty($fields)) {
            $this->setFields($fields);
        }

        if ($this->_viewArg === null) {
            $parts = explode('\\', get_class($this));
            $this->_viewArg = lcfirst((string)array_pop($parts));
        }
    }

    // Collection
    public function setCollection($collection)
    {
        $this->_collection = $collection;
        return $this;
    }

    public function getCollection()
    {
        return $this->_collection;
    }

    public function setMode(string $mode)
    {
        switch ($mode) {
            case 'post':
            case 'get':
                $this->_mode = $mode;
                break;

            default:
                throw Glitch::EInvalidArgument([
                    'message' => 'Invalid paginator mode',
                    'data' => $mode
                ]);
        }

        return $this;
    }

    public function getMode(): string
    {
        return $this->_mode;
    }

    public function setPostEvent(string $event)
    {
        $this->_postEvent = $event;
        return $this;
    }

    public function getPostEvent()
    {
        return $this->_postEvent;
    }

    // Error
    public function setErrorMessage(string $message=null)
    {
        $this->_errorMessage = $message;
        return $this;
    }

    public function getErrorMessage()
    {
        return $this->_errorMessage;
    }

    public function shouldRenderIfEmpty(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_renderIfEmpty = $flag;
            return $this;
        }

        return $this->_renderIfEmpty;
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
        if (is_string($value)) {
            $key = $value;
            $value = true;
        }

        if ($value === true && isset($this->_fields[$key]) && $this->_fields[$key] instanceof core\lang\ICallback) {
            return $this;
        }

        if (is_callable($value)) {
            $value = core\lang\Callback::factory($value);
        }

        $this->_fields[$key] = $value;
        return $this;
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function hideField(...$keys)
    {
        foreach ($keys as $key) {
            if (isset($this->_fields[$key])) {
                $this->_fields[$key] = false;
            }
        }

        return $this;
    }

    public function showField(...$keys)
    {
        foreach ($keys as $key) {
            if (isset($this->_fields[$key]) && $this->_fields[$key] == false) {
                $this->_fields[$key] = true;
            }
        }

        return $this;
    }

    public function isFieldVisible($key): bool
    {
        return isset($this->_fields[$key])
            && $this->_fields[$key] !== false;
    }

    public function addCustomField($key, $callback)
    {
        $this->_fields[$key] = core\lang\Callback::factory($callback);
        return $this;
    }

    // Url redirect
    public function setUrlRedirect($redirect)
    {
        $this->_urlRedirect = $redirect;
        return $this;
    }

    public function getUrlRedirect()
    {
        return $this->_urlRedirect;
    }

    // View arg
    public function setViewArg($arg)
    {
        $this->_viewArg = $arg;
        return $this;
    }

    public function getViewArg()
    {
        return $this->_viewArg;
    }


    // Render
    public function toWidget(): ?aura\html\widget\IWidget
    {
        return $this->render();
    }

    protected function _execute()
    {
        if ($this->_collection === null) {
            if ($this->_viewArg !== null
            && $this->view->hasSlot($this->_viewArg)) {
                $this->_collection = $this->view->getSlot($this->_viewArg);
            }
        }

        $output = $this->view->html->collectionList($this->_collection);
        $output->setMode($this->_mode);
        $output->setPostEvent($this->_postEvent);
        $context = $output->getRendererContext();
        $context->setComponent($this);


        if ($this->_errorMessage !== null) {
            $output->setErrorMessage($this->_errorMessage);
        } else {
            $output->setErrorMessage($this->_('This list is currently empty'));
        }

        if ($this->_renderIfEmpty !== null) {
            $output->shouldRenderIfEmpty($this->_renderIfEmpty);
        }

        foreach ($this->_fields as $key => $value) {
            if ($value === true) {
                $func = 'add'.ucfirst($key).'Field';

                if (method_exists($this, $func)) {
                    $this->{$func}($output);
                } else {
                    $output->addField($key);
                }
            } elseif (is_callable($value)) {
                core\lang\Callback::call($value, $output, $key);
            }
        }

        return $output;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->render());
    }
}
