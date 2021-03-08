<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Component;

use df\arch\component\HeaderBar as HeaderBarBase;
use df\arch\Scaffold;
use df\core\lang\Callback;
use df\aura\html\widget\Menu as MenuWidget;

class HeaderBar extends HeaderBarBase
{
    protected $scaffold;
    protected $name;

    protected $subOperativeLinkBuilder;

    public function __construct(Scaffold $scaffold, string $name, array $args=null)
    {
        $this->scaffold = $scaffold;
        $this->name = ucfirst($name);
        parent::__construct($scaffold->getContext(), $args);
        $this->icon = $scaffold->getDirectoryIcon();
    }

    protected function addOperativeLinks(MenuWidget $menu): void
    {
        $method = 'generate'.$this->name.'OperativeLinks';

        if (method_exists($this->scaffold, $method)) {
            $menu->addLinks($this->scaffold->{$method}());
        }
    }

    protected function addSubOperativeLinks(MenuWidget $menu): void
    {
        $method = 'generate'.$this->name.'SubOperativeLinks';

        if (method_exists($this->scaffold, $method)) {
            $menu->addLinks($this->scaffold->{$method}());
        }

        if ($this->subOperativeLinkBuilder) {
            $this->subOperativeLinkBuilder->invoke($menu, $this->scaffold->view, $this->scaffold);
        }
    }

    protected function addTransitiveLinks(MenuWidget $menu): void
    {
        $method = 'generate'.$this->name.'TransitiveLinks';

        if (method_exists($this->scaffold, $method)) {
            $menu->addLinks($this->scaffold->{$method}());
        }
    }

    protected function addSectionLinks(MenuWidget $menu): void
    {
        $method = 'generate'.$this->name.'SectionLinks';

        if (method_exists($this->scaffold, $method)) {
            $menu->addLinks($this->scaffold->{$method}());
        }

        if (count($menu->getEntries()) == 1) {
            $menu->clearEntries();
        }
    }

    protected function renderSelectorArea()
    {
        return $this->scaffold->renderRecordSwitchers();
    }

    public function setSubOperativeLinkBuilder($builder=null)
    {
        if ($builder !== null) {
            $builder = Callback::factory($builder);
        }

        $this->subOperativeLinkBuilder = $builder;
        return $this;
    }

    public function getSubOperativeLinkBuilder()
    {
        return $this->subOperativeLinkBuilder;
    }
}
