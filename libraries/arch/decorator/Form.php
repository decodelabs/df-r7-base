<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\decorator;

use df\arch;
use df\aura;
use df\core;

abstract class Form implements IFormDecorator
{
    use core\TContextAware;
    use core\lang\TChainable;
    use aura\view\TView_DeferredRenderable;
    use aura\view\TView_CascadingHelperProvider;

    public $values;
    public $content;
    public $form;

    public static function factory(arch\node\IFormNode $form): ?IFormDecorator
    {
        $request = $form->context->location;
        $path = $request->getController();

        if (!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }

        $parts[] = '_decorators';
        $parts[] = ucfirst($request->getNode()) . 'Form';
        $end = implode('\\', $parts);

        $class = 'df\\apex\\directory\\' . $request->getArea() . '\\' . $end;

        if (!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\' . $end;

            if (!class_exists($class)) {
                return null;
            }
        }

        return new $class($form);
    }

    protected function __construct(arch\node\IFormNode $form)
    {
        $this->context = $form->context;
        $this->form = $form;
        $this->view = &$form->view;
        $this->values = &$form->values;
        $this->content = &$form->content;

        $this->init();
    }

    protected function init(): void
    {
    }

    public function reloadDefaultValues(): void
    {
        $this->form->reloadDefaultValues();
    }


    final public function renderUi(): void
    {
        $this->createUi();
    }

    abstract protected function createUi(): void;


    final public function isRenderingInline(): bool
    {
        return $this->form->isRenderingInline();
    }

    final public function getState(): arch\node\form\State
    {
        return $this->form->getState();
    }

    final public function loadDelegate(string $id, string $path): arch\node\IDelegate
    {
        return $this->form->loadDelegate($id, $path);
    }

    final public function directLoadDelegate(string $id, string $class): arch\node\IDelegate
    {
        return $this->form->directLoadDelegate($id, $class);
    }

    final public function proxyLoadDelegate(string $id, arch\node\IDelegateProxy $proxy): arch\node\IDelegate
    {
        return $this->form->proxyLoadDelegate($id, $proxy);
    }

    final public function getDelegate(string $id): arch\node\IDelegate
    {
        return $this->form->getDelegate($id);
    }

    final public function hasDelegate(string $id): bool
    {
        return $this->form->hasDelegate($id);
    }

    /**
     * @return $this
     */
    final public function unloadDelegate(string $id): static
    {
        $this->form->unloadDelegate($id);
        return $this;
    }

    // Helpers
    final public function isValid(): bool
    {
        return $this->form->isValid();
    }

    final public function countErrors(): int
    {
        return $this->form->countErrors();
    }

    final public function fieldName(string $name): string
    {
        return $this->form->fieldName($name);
    }

    final public function eventName(string $name, string ...$args): string
    {
        return $this->form->eventName($name, ...$args);
    }

    final public function elementId(string $name): string
    {
        return $this->form->elementId($name);
    }

    // Store

    /**
     * @return $this
     */
    final public function setStore(
        string $key,
        mixed $value
    ): static {
        $this->form->setStore($key, $value);
        return $this;
    }

    final public function hasStore(string ...$keys): bool
    {
        return $this->form->hasStore(...$keys);
    }

    final public function getStore(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->form->getStore($key, $default);
    }

    /**
     * @return $this
     */
    final public function removeStore(string ...$keys): static
    {
        $this->form->removeStore(...$keys);
        return $this;
    }

    /**
     * @return $this
     */
    final public function clearStore(): static
    {
        $this->form->clearStore();
        return $this;
    }

    // ArrayAccess
    final public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->form->offsetSet($key, $value);
    }

    final public function offsetGet(mixed $key): mixed
    {
        return $this->form->offsetGet($key);
    }

    final public function offsetExists(mixed $key): bool
    {
        return $this->form->offsetExists($key);
    }

    final public function offsetUnset(mixed $key): void
    {
        $this->form->offsetUnset($key);
    }
}
