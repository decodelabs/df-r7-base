<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\entry;

use df;
use df\core;
use df\arch;

class None extends Base {

    protected $_id;

    protected static function _fromArray(array $entry): arch\navigation\IEntry {
        return new self($entry['id'] ?? null);
    }

    public function __construct($id) {
        $this->setId($id);
    }

    public function setId(?string $id) {
        $this->_id = $id;

        return $this;
    }

    public function getId(): ?string {
        return $this->_id;
    }

    public function toArray(): array {
        return [
            'type' => 'None',
            'weight' => $this->_weight,
            'id' => $this->_id
        ];
    }
}
