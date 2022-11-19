<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\form;

use df\arch;
use df\core;

class State implements arch\node\IFormState, \Serializable
{
    public $sessionId;
    public $values;
    public $isOperating = false;
    public $referrer;

    protected $_isNew = false;
    protected $_delegates = [];
    protected $_store = [];

    public function __construct(string $sessionId)
    {
        $this->sessionId = $sessionId;
        $this->values = new core\collection\InputTree();
        $this->_isNew = true;
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        return $this->_getSerializeValues(true);
    }


    protected function _getSerializeValues(bool $withId = true): array
    {
        $output = [];

        if ($withId) {
            $output['id'] = $this->sessionId;
        }

        if (!$this->values->isEmpty()) {
            $output['vl'] = $this->values;
        }

        if ($this->_isNew) {
            $output['nw'] = true;
        }

        if ($this->referrer) {
            $output['rf'] = $this->referrer;
        }

        if (!empty($this->_delegates)) {
            $delegates = [];

            foreach ($this->_delegates as $key => $delegate) {
                $delegates[$key] = $delegate->_getSerializeValues(false);
            }

            $output['dl'] = $delegates;
        }

        if (!empty($this->_store)) {
            $output['st'] = $this->_store;
        }

        return $output;
    }

    public function unserialize(string $data): void
    {
        if (is_array($values = unserialize($data))) {
            $this->__unserialize($values);
        }
    }

    public function __unserialize(array $data): void
    {
        $this->_setUnserializedValues($data, $data['id']);
    }

    protected function _setUnserializedValues(array $values, string $id)
    {
        $this->_isNew = false;
        $this->sessionId = $id;

        if (isset($values['vl'])) {
            $this->values = $values['vl'];
        } elseif (!$this->values) {
            $this->values = new core\collection\InputTree();
        }

        if (isset($values['nw'])) {
            $this->_isNew = true;
        }

        if (isset($values['rf'])) {
            $this->referrer = $values['rf'];
        }

        if (isset($values['dl'])) {
            foreach ($values['dl'] as $key => $delegateData) {
                $delegate = new self($this->sessionId);
                $delegate->_setUnserializedValues($delegateData, $id);

                $this->_delegates[$key] = $delegate;
            }
        }

        if (isset($values['st']) && is_array($values['st'])) {
            $this->_store = $values['st'];
        }
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getValues(): core\collection\IInputTree
    {
        return $this->values;
    }


    public function getDelegateState(string $id): State
    {
        if (!isset($this->_delegates[$id])) {
            $this->_delegates[$id] = new self($this->sessionId);
        }

        return $this->_delegates[$id];
    }

    public function clearDelegateState($id)
    {
        if (isset($this->_delegates[$id])) {
            $this->_delegates[$id]->reset();
            unset($this->_delegates[$id]);
        }

        return $this;
    }


    public function isNew(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isNew = $flag;

            foreach ($this->_delegates as $delegate) {
                $delegate->isNew($flag);
            }

            return $this;
        }

        return (bool)$this->_isNew;
    }

    public function reset(): static
    {
        $this->values->clear();
        $this->_isNew = true;

        foreach ($this->_delegates as $delegate) {
            $delegate->reset();
        }

        $this->clearStore();
        $this->isOperating = true;

        return $this;
    }

    public function isOperating(): bool
    {
        if ($this->isOperating) {
            return true;
        }

        foreach ($this->_delegates as $child) {
            if ($child->isOperating()) {
                return true;
            }
        }

        return false;
    }


    // Store

    /**
     * @return $this
     */
    public function setStore(
        string $key,
        mixed $value
    ): static {
        $this->isOperating = true;
        $this->_store[$key] = $value;
        return $this;
    }

    public function hasStore(string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (isset($this->_store[$key])) {
                return true;
            }
        }

        return false;
    }

    public function getStore(
        string $key,
        mixed $default = null
    ): mixed {
        if (isset($this->_store[$key])) {
            return $this->_store[$key];
        }

        return $default;
    }

    /**
     * @return $this
     */
    public function removeStore(string ...$keys): static
    {
        foreach ($keys as $key) {
            unset($this->_store[$key]);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function clearStore(): static
    {
        $this->_store = [];
        return $this;
    }
}
