<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit;

use df;
use df\core;
use df\axis;

abstract class FileStore extends core\cache\FileStore implements axis\IUnit {

    use axis\TUnit;

    public static function createCacheId(): string {
        $parts = explode('\\', get_called_class());
        $parts = array_slice($parts, 3, -1);
        return 'axis/unit/'.implode('/', $parts);
    }

    public function __construct(axis\IModel $model) {
        $this->_model = $model;
        parent::__construct();
    }

    public function getUnitType() {
        return 'cache';
    }

    public function getUnitAdapter() {
        return $this->getCacheBackend();
    }

    public function getStorageBackendName() {
        return $this->getCacheId();
    }
}
