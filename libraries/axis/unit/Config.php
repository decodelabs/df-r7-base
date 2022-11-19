<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit;

use df\axis;
use df\core;

abstract class Config extends core\Config implements axis\IUnit
{
    use axis\TUnit;

    public function __construct(axis\IModel $model)
    {
        $this->_model = $model;
        $id = static::ID;

        if ($id === null) {
            $id = 'model/' . $model->getModelName() . '.' . $this->getCanonicalUnitName();
        }

        parent::__construct($id);
    }

    public function getUnitType()
    {
        return 'config';
    }

    public function getStorageBackendName()
    {
        return $this->getConfigId();
    }
}
