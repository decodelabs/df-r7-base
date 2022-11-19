<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\component;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\aura;
use df\core;

class CollectionList extends Base implements aura\html\widget\IWidgetProxy, Dumpable
{
    public const DEFAULT_ERROR_MESSAGE = null;

    protected $collection;
    protected $errorMessage;
    protected $renderIfEmpty = null;
    protected $fields = [];
    protected $urlRedirect = null;
    protected $viewArg;
    protected $mode = 'get';
    protected $postEvent = 'paginate';

    protected function init(array $fields = null, $collection = null)
    {
        if (static::DEFAULT_ERROR_MESSAGE !== null) {
            $this->errorMessage = $this->_(static::DEFAULT_ERROR_MESSAGE);
        }

        if ($collection) {
            $this->setCollection($collection);
        }

        if (!empty($fields)) {
            $this->setFields($fields);
        }

        if ($this->viewArg === null) {
            $parts = explode('\\', get_class($this));
            $this->viewArg = lcfirst((string)array_pop($parts));
        }
    }

    // Collection
    public function setCollection($collection)
    {
        $this->collection = $collection;
        return $this;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @return $this
     */
    public function setMode(string $mode): static
    {
        switch ($mode) {
            case 'post':
            case 'get':
                $this->mode = $mode;
                break;

            default:
                throw Exceptional::InvalidArgument([
                    'message' => 'Invalid paginator mode',
                    'data' => $mode
                ]);
        }

        return $this;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function setPostEvent(string $event)
    {
        $this->postEvent = $event;
        return $this;
    }

    public function getPostEvent()
    {
        return $this->postEvent;
    }

    // Error
    public function setErrorMessage(string $message = null)
    {
        $this->errorMessage = $message;
        return $this;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function shouldRenderIfEmpty(bool $flag = null)
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
        if (is_string($value)) {
            $key = $value;
            $value = true;
        }

        if ($value === true && isset($this->fields[$key]) && $this->fields[$key] instanceof core\lang\ICallback) {
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

    // Url redirect
    public function setUrlRedirect($redirect)
    {
        $this->urlRedirect = $redirect;
        return $this;
    }

    public function getUrlRedirect()
    {
        return $this->urlRedirect;
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
        if ($this->collection === null) {
            if ($this->viewArg !== null
            && $this->view->hasSlot($this->viewArg)) {
                $this->collection = $this->view->getSlot($this->viewArg);
            }
        }

        $output = $this->view->html->collectionList($this->collection);
        $output->setMode($this->mode);
        $output->setPostEvent($this->postEvent);
        $context = $output->getRendererContext();
        $context->setComponent($this);


        if ($this->errorMessage !== null) {
            $output->setErrorMessage($this->errorMessage);
        } else {
            $output->setErrorMessage($this->_('This list is currently empty'));
        }

        if ($this->renderIfEmpty !== null) {
            $output->shouldRenderIfEmpty($this->renderIfEmpty);
        }

        foreach ($this->fields as $key => $value) {
            if ($value === true) {
                $func = 'add' . ucfirst($key) . 'Field';

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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->render();
    }
}
