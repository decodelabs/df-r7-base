<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\fortify;

use df\axis;
use df\core;

interface IFortify extends core\IContextAware
{
    public function getUnit(): axis\IUnit;
    public function getModel(): axis\IModel;
    public function getName(): string;

    public function dispatch();
}
