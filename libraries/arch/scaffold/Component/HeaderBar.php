<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Component;

use df\arch\component\HeaderBar as HeaderBarBase;
use df\arch\scaffold\IScaffold as Scaffold;
use df\core\lang\Callback;

class HeaderBar extends HeaderBarBase
{
    protected $scaffold;
    protected $name;

    protected $_subOperativeLinkBuilder;

    public function __construct(Scaffold $scaffold, string $name, array $args=null)
    {
        $this->scaffold = $scaffold;
        $this->name = ucfirst($name);
        parent::__construct($scaffold->getContext(), $args);
        $this->icon = $scaffold->getDirectoryIcon();
    }

    protected function _addOperativeLinks($menu)
    {
        $method = 'add'.$this->name.'OperativeLinks';

        if (method_exists($this->scaffold, $method)) {
            $this->scaffold->{$method}($menu, $this);
        }
    }

    protected function _addSubOperativeLinks($menu)
    {
        $method = 'add'.$this->name.'SubOperativeLinks';

        if (method_exists($this->scaffold, $method)) {
            $this->scaffold->{$method}($menu, $this);
        }

        if ($this->_subOperativeLinkBuilder) {
            $this->_subOperativeLinkBuilder->invoke($menu, $this->scaffold->view, $this->scaffold);
        }
    }

    protected function _addTransitiveLinks($menu)
    {
        $method = 'add'.$this->name.'TransitiveLinks';

        if (method_exists($this->scaffold, $method)) {
            $this->scaffold->{$method}($menu, $this);
        }
    }

    protected function _addSectionLinks($menu)
    {
        $method = 'add'.$this->name.'SectionLinks';

        if (method_exists($this->scaffold, $method)) {
            $this->scaffold->{$method}($menu, $this);
        }
    }

    protected function _renderSelectorArea()
    {
        $method = 'render'.$this->name.'SelectorArea';

        if (method_exists($this->scaffold, $method)) {
            return $this->scaffold->{$method}($this);
        }
    }

    public function setSubOperativeLinkBuilder($builder=null)
    {
        if ($builder !== null) {
            $builder = Callback::factory($builder);
        }

        $this->_subOperativeLinkBuilder = $builder;
        return $this;
    }

    public function getSubOperativeLinkBuilder()
    {
        return $this->_subOperativeLinkBuilder;
    }
}
