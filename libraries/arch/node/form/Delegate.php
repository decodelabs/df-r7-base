<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\form;

use df\core;
use df\arch;
use df\aura;

use df\arch\node\IDelegate;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Fluidity\CastTrait;

class Delegate implements IDelegate
{
    use core\TContextAware;
    use arch\node\TForm;
    use CastTrait;

    public const DEFAULT_REDIRECT = null;

    protected $_delegateId;
    private $_isNew = false;
    private $_isComplete = false;

    public function __construct(
        arch\IContext $context,
        arch\node\form\State $state,
        arch\node\IFormEventDescriptor $event,
        string $id
    ) {
        $this->context = $context;
        $this->_state = $state;
        $this->_delegateId = $id;

        $this->event = $event;
        $this->values = $state->getValues();
        $this->afterConstruct();
    }

    protected function afterConstruct(): void
    {
    }

    public function getDelegateId(): string
    {
        return $this->_delegateId;
    }

    public function getDelegateKey(): string
    {
        $parts = explode('.', $this->_delegateId);
        return (string)array_pop($parts);
    }

    final public function initialize()
    {
        $this->beginInitialize();
        $this->endInitialize();
        return $this;
    }

    final public function beginInitialize()
    {
        $this->init();
        $this->loadDelegates();

        if ($this->_state->isNew()) {
            $this->_isNew = true;
            $this->setDefaultValues();
        }

        foreach ($this->_delegates as $delegate) {
            $response = $delegate->beginInitialize();

            if (!empty($response)) {
                return $response;
            }
        }

        return null;
    }

    final public function endInitialize()
    {
        foreach ($this->_delegates as $delegate) {
            $delegate->endInitialize();
        }

        if ($this instanceof arch\node\IDependentDelegate) {
            $this->normalizeDependencyValues();
        }

        $this->_state->isNew(false);
        $this->afterInit();

        return $this;
    }

    public function isNew(): bool
    {
        return $this->_isNew;
    }

    public function setRenderContext(
        aura\view\IView $view,
        aura\view\content\WidgetContentProvider $content,
        $isRenderingInline=false
    ): static {
        $this->view = $view;
        $this->content = $content;
        $this->_isRenderingInline = $isRenderingInline;

        foreach ($this->_delegates as $delegate) {
            $delegate->setRenderContext($view, $content);
        }

        return $this;
    }


    public function setComplete(): void
    {
        $this->_isComplete = true;
        $this->onComplete();

        foreach ($this->_delegates as $delegate) {
            $delegate->setComplete();
        }

        $this->_state->reset();
    }

    public function isComplete(): bool
    {
        return $this->_isComplete;
    }

    protected function onComplete()
    {
    }


    protected function getDefaultRedirect(): ?string
    {
        return static::DEFAULT_REDIRECT;
    }


    // State

    /**
     * @return $this
     */
    public function reset(): static
    {
        $this->_state->reset();

        foreach ($this->_delegates as $id => $delegate) {
            $this->unloadDelegate($id);
        }

        $this->afterReset();

        $this->loadDelegates();
        $this->setDefaultValues();

        foreach ($this->_delegates as $id => $delegate) {
            $delegate->initialize();
        }

        $this->_state->isNew(false);
        $this->afterInit();

        return $this;
    }

    protected function afterReset(): void
    {
    }



    // Events
    protected function onCancelEvent(): mixed
    {
        $this->setComplete();
        return $this->_getCompleteRedirect();
    }


    // Names
    public function fieldName(string $name): string
    {
        $parts = explode('[', $name, 2);
        $parts[0] .= ']';

        return '_delegates['.$this->_delegateId.']['.implode('[', $parts);
    }

    public function elementId(string $name): string
    {
        return Dictum::slug($this->getDelegateId().'-'.$name);
    }



    public function getStateData(): array
    {
        $output = [
            'isValid' => $this->isValid(),
            'isNew' => $this->_isNew,
            'values' => $this->values->toArrayDelimitedSet('_delegates['.$this->_delegateId.']'),
            'errors' => []
        ];

        foreach ($this->_delegates as $delegate) {
            $delegateState = $delegate->getStateData();

            if (!$delegateState['isValid']) {
                $output['isValid'] = false;
            }

            $output['values'] = array_merge($output['values'], $delegateState['values']);
            $output['errors'] = array_merge($output['errors'], $delegateState['errors']);
        }

        return $output;
    }
}
