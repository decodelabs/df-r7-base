<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail;

trait SavableTrait
{
    public function save(): void
    {
        $loader = Dovetail::getLoaderFor($this->manifest);
        $loader->saveConfig($this->manifest, $this->data->toArray());
    }
}
