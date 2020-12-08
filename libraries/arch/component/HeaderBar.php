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
use DecodeLabs\Glitch\Dumpable;

abstract class HeaderBar extends Base implements Dumpable
{
    protected $record;
    protected $title;
    protected $subTitle;
    protected $backLinkRequest;
    protected $icon;

    protected function init($record=null, $title=null, $subTitle=null)
    {
        $this->setRecord($record);
        $this->setTitle($title);
        $this->setSubTitle($subTitle);
    }


    // Record
    public function setRecord($record)
    {
        $this->record = $record;
        return $this;
    }

    public function getRecord()
    {
        return $this->record;
    }


    // Title
    public function setTitle($title)
    {
        if (empty($title)) {
            $title = null;
        }

        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }


    // Sub title
    public function setSubTitle(string $subTitle=null)
    {
        if (empty($subTitle)) {
            $subTitle = null;
        }

        $this->subTitle = $subTitle;
        return $this;
    }

    public function getSubTitle()
    {
        return $this->subTitle;
    }


    // Back link
    public function setBackLinkRequest($request)
    {
        $this->backLinkRequest = $request;
        return $this;
    }

    public function getBackLinkRequest()
    {
        return $this->backLinkRequest;
    }


    // Icon
    public function setIcon(string $icon=null)
    {
        $this->icon = $icon;
        return $this;
    }

    public function getIcon()
    {
        return $this->icon;
    }



    // Render
    protected function _execute()
    {
        $output = [];

        // Title
        if (empty($this->title)) {
            $title = $this->_getDefaultTitle();
        } else {
            $title = $this->title;
        }

        if ($title !== null) {
            $output[] = $title = Html::{'h2'}($title);

            if ($this->icon) {
                $title->prepend($this->html->icon($this->icon));
            }
        }


        // SubTitle

        if (empty($this->subTitle)) {
            $subTitle = $this->_getDefaultSubTitle();
        } else {
            $subTitle = $this->subTitle;
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
        $primaryMenu->addLink($this->html->backLink($this->backLinkRequest));

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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->render();
    }
}
