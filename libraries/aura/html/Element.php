<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html;

use df;
use df\core;
use df\aura;
use df\flex;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Element extends Tag implements IElement, Inspectable
{
    use TElementContent;

    public function __construct($name, $content=null, array $attributes=null)
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
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setDefinition($this->toString());
    }
}
