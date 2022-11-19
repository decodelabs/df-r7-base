<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\decorator;

use df\arch;
use df\aura;
use df\core;

abstract class Delegate implements IDelegateDecorator
{
    use core\TContextAware;
    use core\lang\TChainable;
    use aura\view\TView_DeferredRenderable;
    use aura\view\TView_CascadingHelperProvider;

    public $values;
    public $content;
    public $delegate;

    public static function factory(arch\node\IDelegate $delegate): ?IDelegateDecorator
    {
        $request = $delegate->context->location;
        $path = $request->getController();

        if (!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }

        $dClass = explode('\\', get_class($delegate));
        $name = array_pop($dClass);

        $parts[] = '_decorators';
        $parts[] = $name . 'Delegate';
        $end = implode('\\', $parts);

        $class = 'df\\apex\\directory\\' . $request->getArea() . '\\' . $end;

        if (!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\' . $end;

            if (!class_exists($class)) {
                return null;
            }
        }

        return new $class($delegate);
    }

    protected function __construct(arch\node\IDelegate $delegate)
    {
        $this->context = $delegate->context;
        $this->delegate = $delegate;
        $this->view = &$delegate->view;
        $this->values = &$delegate->values;
        $this->content = &$delegate->content;

        $this->init();
    }


    protected function init(): void
    {
    }

    public function reloadDefaultValues(): void
    {
        $this->delegate->reloadDefaultValues();
    }

    final public function renderUi(): void
    {
        $this->createUi();
    }

    protected function createUi(): void
    {
        // add default message?
    }


    final public function isRenderingInline(): bool
    {
        return $this->delegate->isRenderingInline();
    }

    final public function getState(): arch\node\form\State
    {
        return $this->delegate->getState();
    }

    final public function loadDelegate(string $id, string $path): arch\node\IDelegate
    {
        return $this->delegate->loadDelegate($id, $path);
    }

    final public function directLoadDelegate(string $id, string $class): arch\node\IDelegate
    {
        return $this->delegate->directLoadDelegate($id, $class);
    }

    final public function proxyLoadDelegate(string $id, arch\node\IDelegateProxy $proxy): arch\node\IDelegate
    {
        return $this->delegate->proxyLoadDelegate($id, $proxy);
    }

    final public function getDelegate(string $id): arch\node\IDelegate
    {
        return $this->delegate->getDelegate($id);
    }

    final public function hasDelegate(string $id): bool
    {
        return $this->delegate->hasDelegate($id);
    }

    /**
     * @return $this
     */
    final public function unloadDelegate(string $id): static
    {
        $this->delegate->unloadDelegate($id);
        return $this;
    }

    // Helpers
    final public function isValid(): bool
    {
        return $this->delegate->isValid();
    }

    final public function countErrors(): int
    {
        return $this->delegate->countErrors();
    }

    final public function fieldName(string $name): string
    {
        return $this->delegate->fieldName($name);
    }

    final public function eventName(string $name, string ...$args): string
    {
        return $this->delegate->eventName($name, ...$args);
    }

    final public function elementId(string $name): string
    {
        return $this->delegate->elementId($name);
    }

    // Store

    /**
     * @return $this
     */
    final public function setStore(
        string $key,
        mixed $value
    ): static {
        $this->delegate->setStore($key, $value);
        return $this;
    }

    final public function hasStore(string ...$keys): bool
    {
        return $this->delegate->hasStore(...$keys);
    }

    final public function getStore(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->delegate->getStore($key, $default);
    }

    /**
     * @return $this
     */
    final public function removeStore(string ...$keys): static
    {
        $this->delegate->removeStore(...$keys);
        return $this;
    }

    /**
     * @return $this
     */
    final public function clearStore(): static
    {
        $this->delegate->clearStore();
        return $this;
    }

    // ArrayAccess
    final public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->delegate->offsetSet($key, $value);
    }

    final public function offsetGet(mixed $key): mixed
    {
        return $this->delegate->offsetGet($key);
    }

    final public function offsetExists(mixed $key): bool
    {
        return $this->delegate->offsetExists($key);
    }

    final public function offsetUnset(mixed $key): void
    {
        $this->delegate->offsetUnset($key);
    }
}
