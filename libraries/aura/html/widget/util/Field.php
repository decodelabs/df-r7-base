<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget\util;

use df;
use df\core;
use df\aura;
use df\flex;

use DecodeLabs\Glitch\Dumpable;

class Field implements aura\html\widget\IField, Dumpable
{
    public $key;
    public $name;
    public $labels = [];
    public $renderer;

    public function __construct(string $key, string $name, callable $renderer=null)
    {
        $this->key = $key;
        $this->setName($name);
        $this->setRenderer($renderer);
    }


    // Key
    public function getKey(): string
    {
        return $this->key;
    }

    // Name
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }


    // Labels
    public function addLabel(string $key, string $label=null)
    {
        if (empty($label)) {
            $label = flex\Text::formatLabel($key);
        }

        $this->labels[$key] = $label;
        return $this;
    }

    public function removeLabel(string $key)
    {
        unset($this->labels[$key]);
        return $this;
    }

    public function getLabels(): array
    {
        return $this->labels;
    }

    public function getHeaderList(): array
    {
        return array_merge([$this->key => $this->name], $this->labels);
    }

    // Renderer
    public function setRenderer(callable $renderer=null)
    {
        if ($renderer !== null) {
            $renderer = core\lang\Callback::factory($renderer);
        }

        $this->renderer = $renderer;
        return $this;
    }

    public function getRenderer(): ?callable
    {
        return $this->renderer;
    }

    public function render($data, aura\html\widget\IRendererContext $renderContext)
    {
        return $renderContext->renderCell($data, $this->renderer);
    }

    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'property:key' => $this->key;

        if ($this->name != $this->key) {
            yield 'property:name' => $this->name;
        }

        if (!$this->renderer instanceof \Closure) {
            yield 'property:renderer' => $this->renderer;
        }
    }
}
