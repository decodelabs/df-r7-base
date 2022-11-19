<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mint\charge;

use df\mint;

class Capture implements mint\IChargeCapture
{
    protected $_id;

    public function __construct(string $id)
    {
        $this->setId($id);
    }

    public function setId(string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->_id;
    }
}
