<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map\aspect;

use df;
use df\core;
use df\iris;

class EntityNamespace extends iris\map\Node implements iris\map\IAspect, core\IStringProvider, core\collection\IQueue, core\IDumpable {

    use core\collection\TArrayCollection_Queue;
    use core\TStringProvider;

    public static function root(iris\ILocationProvider $locationProvider) {
        return new self($locationProvider, ['__root__']);
    }

    public function __construct(iris\ILocationProvider $locationProvider, ...$input) {
        parent::__construct($locationProvider);

        if(!empty($input)) {
            $this->import(...$input);
        }
    }

    public function toString(): string {
        return implode('.', $this->_collection);
    }

    public function getDumpProperties() {
        return $this->toString();
    }
}