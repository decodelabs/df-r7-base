<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component;

use DecodeLabs\Glitch\Dumpable;

use DecodeLabs\Tagged as Html;
use df\aura\html\widget\Menu as MenuWidget;

abstract class HeaderBar extends Base implements Dumpable
{
    protected $record;
    protected $title;
    protected $subTitle;
    protected $backLinkRequest;
    protected $icon;

    protected function init($record = null, $title = null, $subTitle = null)
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
    public function setSubTitle(string $subTitle = null)
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
    public function setIcon(string $icon = null)
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
            $title = $this->getDefaultTitle();
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
            $subTitle = $this->getDefaultSubTitle();
        } else {
            $subTitle = $this->subTitle;
        }

        if ($subTitle !== null) {
            $output[] = Html::{'h3'}($subTitle);
        }

        // Selector area
        if ($selectorArea = $this->renderSelectorArea()) {
            $output[] = Html::{'div.w.selectorArea.floated'}([$selectorArea]);
        }


        // Primary
        $output[] = $primaryMenu = $this->html->menuBar()->addClass('primary');

        $this->addOperativeLinks($primaryMenu);
        $primaryMenu->addSpacer();
        $this->addSubOperativeLinks($primaryMenu);
        $primaryMenu->addSpacer();
        $this->addTransitiveLinks($primaryMenu);
        $primaryMenu->addLink($this->html->backLink($this->backLinkRequest));

        // Secondary
        $output[] = $secondaryMenu = $this->html->menuBar()->addClass('secondary');
        $this->addSectionLinks($secondaryMenu);

        return Html::{'header.w.bar'}($output);
    }


    protected function getDefaultTitle()
    {
    }
    protected function getDefaultSubTitle()
    {
    }

    protected function addOperativeLinks(MenuWidget $primaryMenu): void
    {
    }
    protected function addSubOperativeLinks(MenuWidget $primaryMenu): void
    {
    }
    protected function addTransitiveLinks(MenuWidget $primaryMenu): void
    {
    }
    protected function addSectionLinks(MenuWidget $secondaryMenu): void
    {
    }
    protected function renderSelectorArea()
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
