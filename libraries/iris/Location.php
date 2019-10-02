<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Location implements ILocation, core\IStringProvider, Inspectable
{
    use TSourceUriProvider;
    use TLocation;
    use core\TStringProvider;

    public function __construct($uri, $line=1, $column=1)
    {
        $this->setSourceUri($uri);
        $this->setLine($line);
        $this->setColumn($column);
    }

    public function toString(): string
    {
        $output = $this->getSourceUri();

        if (empty($output)) {
            $output = '?';
        }

        return $output.' ['.$this->getLine().':'.$this->getColumn().']';
    }

    public function getLocation()
    {
        return $this;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->toString());
    }
}
