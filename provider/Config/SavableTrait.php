<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace DecodeLabs\R7\Config;

use DecodeLabs\Dovetail;
use DecodeLabs\Dovetail\Manifest;
use DecodeLabs\Genesis;

trait SavableTrait
{
    public function save(): void
    {
        $loader = Dovetail::getLoaderFor($this->manifest);
        $loader->saveConfig($this->manifest, $this->data->toArray());

        if (Genesis::$build->isCompiled()) {
            $path = Genesis::$hub->getApplicationPath() . '/config/' . $this->manifest->getName() . '.php';

            $manifest = new Manifest(
                $this->manifest->getName(),
                $path,
                $this->manifest->getFormat()
            );

            $loader->saveConfig($manifest, $this->data->toArray());
        }

        $this->onSave();
    }

    protected function onSave(): void
    {
    }
}
