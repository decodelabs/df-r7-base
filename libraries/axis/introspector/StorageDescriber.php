<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\introspector;

use df;
use df\core;
use df\axis;
use df\opal;

class StorageDescriber implements IStorageDescriber {

    public $name;
    public $type;
    public $itemCount;
    public $size;
    public $indexSize;
    public $creationDate;

    public function __construct($name, $type, $itemCount, $size, $indexSize, $creationDate) {
        $this->name = (string)$name;
        $this->type = (string)$type;
        $this->itemCount = (int)$itemCount;
        $this->size = (int)$size;
        $this->indexSize = (int)$indexSize;
        $this->creationDate = core\time\Date::normalize($creationDate);
    }

    public function getName(): string {
        return $this->name;
    }

    public function getType() {
        return $this->type;
    }

    public function getItemCount() {
        return $this->itemCount;
    }

    public function getSize() {
        return $this->size;
    }

    public function getIndexSize() {
        return $this->indexSize;
    }

    public function getCreationDate() {
        return $this->creationDate;
    }
}
