<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fuse;

use df\core;

class Config extends core\Config
{
    public const ID = 'Fuse';

    public function getDefaultValues(): array
    {
        return [
            'dependencies' => []
        ];
    }

    public function getDependencies()
    {
        return $this->values->dependencies->toArray();
    }
}
