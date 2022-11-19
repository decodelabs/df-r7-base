<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Component;

use df\arch\component\Base as ComponentBase;
use df\arch\Scaffold;

class Generic extends ComponentBase
{
    protected $scaffold;
    protected $name;

    public function __construct(Scaffold $scaffold, string $name, array $args = null)
    {
        $this->scaffold = $scaffold;
        $this->name = $name;
        parent::__construct($scaffold->getContext(), $args);
    }

    protected function _execute()
    {
        $method = 'generate' . $this->name . 'Component';
        return $this->scaffold->{$method}(...$this->getComponentArgs());
    }
}
