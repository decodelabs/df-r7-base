<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component;

use df;
use df\core;
use df\arch;
use df\aura;

use DecodeLabs\Tagged\Html;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

abstract class HeaderBar extends Base implements Inspectable
{
    protected $_record;
    protected $_title;
    protected $_subTitle;
    protected $_backLinkRequest;
    protected $_icon;

    protected function init($record=null, $title=null, $subTitle=null)
    {
        $this->setRecord($record);
        $this->setTitle($title);
        $this->setSubTitle($subTitle);
    }


    // Record
    public function setRecord($record)
    {
        $this->_record = $record;
        return $this;
    }

    public function getRecord()
    {
        return $this->_record;
    }


    // Title
    public function setTitle($title)
    {
        if (empty($title)) {
            $title = null;
        }

        $this->_title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->_title;
    }


    // Sub title
    public function setSubTitle(string $subTitle=null)
    {
        if (empty($subTitle)) {
            $subTitle = null;
        }

        $this->_subTitle = $subTitle;
        return $this;
    }

    public function getSubTitle()
    {
        return $this->_subTitle;
    }


    // Back link
    public function setBackLinkRequest($request)
    {
        $this->_backLinkRequest = $request;
        return $this;
    }

    public function getBackLinkRequest()
    {
        return $this->_backLinkRequest;
    }


    // Icon
    public function setIcon(string $icon=null)
    {
        $this->_icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        return $this->_icon;
    }



    // Render
    protected function _execute()
    {
        $output = [];

        // Title
        if (empty($this->_title)) {
            $title = $this->_getDefaultTitle();
        } else {
            $title = $this->_title;
        }

        if ($title !== null) {
            $output[] = $title = Html::{'h2'}($title);

            if ($this->_icon) {
                $title->prepend($this->html->icon($this->_icon));
            }
        }


        // SubTitle

        if (empty($this->_subTitle)) {
            $subTitle = $this->_getDefaultSubTitle();
        } else {
            $subTitle = $this->_subTitle;
        }

        if ($subTitle !== null) {
            $output[] = Html::{'h3'}($subTitle);
        }

        // Selector area
        if ($selectorArea = $this->_renderSelectorArea()) {
            $output[] = Html::{'div.w.selectorArea.floated'}([$selectorArea]);
        }


        // Primary
        $output[] = $primaryMenu = $this->html->menuBar()->addClass('primary');

        $this->_addOperativeLinks($primaryMenu);
        $primaryMenu->addSpacer();
        $this->_addSubOperativeLinks($primaryMenu);
        $primaryMenu->addSpacer();
        $this->_addTransitiveLinks($primaryMenu);
        $primaryMenu->addLink($this->html->backLink($this->_backLinkRequest));

        // Secondary
        $output[] = $secondaryMenu = $this->html->menuBar()->addClass('secondary');
        $this->_addSectionLinks($secondaryMenu);

        return Html::{'header.w.bar'}($output);
    }


    protected function _getDefaultTitle()
    {
    }
    protected function _getDefaultSubTitle()
    {
    }

    protected function _addOperativeLinks($primaryMenu)
    {
    }
    protected function _addSubOperativeLinks($primaryMenu)
    {
    }
    protected function _addTransitiveLinks($primaryMenu)
    {
    }
    protected function _addSectionLinks($secondaryMenu)
    {
    }
    protected function _renderSelectorArea()
    {
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->render());
    }
}
