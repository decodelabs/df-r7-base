<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Navigation;

use df\arch\Context;
use df\arch\scaffold\IScaffold as Scaffold;
use df\arch\scaffold\Base as ScaffoldBase;
use df\arch\navigation\menu\Base as MenuBase;

class Menu extends MenuBase
{
    protected $scaffold;
    protected $name;

    public function __construct(Scaffold $scaffold, string $name, $id)
    {
        $this->scaffold = $scaffold;
        $this->name = $name;
        parent::__construct($scaffold->getContext(), $id);
    }

    protected function createEntries($entryList)
    {
        $method = 'generate'.ucfirst($this->name).'Menu';

        if (method_exists($this->scaffold, $method)) {
            $this->scaffold->{$method}($entryList);
        }
    }

    protected function _getStorageArray()
    {
        return array_merge(parent::_getStorageArray(), [
            'name' => $this->name
        ]);
    }

    protected function _setStorageArray(array $data)
    {
        parent::_setStorageArray($data);

        $this->name = $data['name'];

        if (!$this->scaffold) {
            if (!$this->context) {
                $this->context = Context::getCurrent();
            }

            $this->scaffold = ScaffoldBase::factory($this->context);
        }
    }
}
