<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis;

use df\axis;
use df\core;

final class Context implements IContext
{
    use axis\TUnit;
    use core\TContext;

    public $model;

    public function __construct(IModel $model)
    {
        $this->_model = $this->model = $model;
    }

    public function getUnitType()
    {
        return 'context';
    }

    public function getUnitName()
    {
        return 'context';
    }

    public function __get($key)
    {
        return $this->getHelper($key);
    }


    // Helpers
    protected function _loadHelper($name)
    {
        return $this->loadRootHelper($name);
    }
}
