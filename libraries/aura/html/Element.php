<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use DecodeLabs\Glitch\Dumpable;

class Element extends Tag implements IElement, Dumpable
{
    use TElementContent;

    public function __construct($name, $content = null, array $attributes = null)
    {
        parent::__construct($name, $attributes);

        if ($content !== null) {
            $this->import($content);
        }
    }

    public function toString(): string
    {
        return (string)$this->renderWith($this);
    }

    public function render()
    {
        return $this->renderWith($this);
    }

    public function setBody($body)
    {
        $this->clear()->push($body);
        return $this;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => $this->toString();
    }
}
