<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map\statement;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Comment extends iris\map\Node implements iris\map\IStatement, Inspectable
{
    protected $_body;

    public function __construct(iris\ILocationProvider $locationProvider, $body)
    {
        parent::__construct($locationProvider);

        $this->setBody($body);
    }

    public function setBody($body)
    {
        $this->_body = (string)$body;
        return $this;
    }

    public function getBody()
    {
        return $this->_body;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText(trim($this->_body));
    }
}
