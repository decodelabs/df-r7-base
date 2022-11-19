<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold\Navigation;

use DecodeLabs\R7\Legacy;
use df\arch\navigation\menu\Base as MenuBase;
use df\arch\Scaffold;

use df\arch\scaffold\Loader as ScaffoldLoader;

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
        $method = 'generate' . ucfirst($this->name) . 'Menu';

        if (method_exists($this->scaffold, $method)) {
            $this->scaffold->{$method}($entryList);
        }
    }

    public function __serialize(): array
    {
        return array_merge(parent::__serialize(), [
            'name' => $this->name
        ]);
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->name = $data['name'];

        if (!$this->scaffold) {
            if (!$this->context) {
                $this->context = Legacy::getContext();
            }

            $this->scaffold = ScaffoldLoader::fromContext($this->context);
        }
    }
}
