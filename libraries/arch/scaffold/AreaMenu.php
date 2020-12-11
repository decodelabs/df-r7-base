<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df\arch\scaffold\Index\Decorator as IndexDecorator;
use df\arch\scaffold\Index\DecoratorTrait as IndexDecoratorTrait;

class AreaMenu extends Generic implements
    IndexDecorator
{
    const HEADER_BAR = true;

    use IndexDecoratorTrait;

    public function indexHtmlNode()
    {
        $view = $this->apex->newWidgetView();

        if (static::HEADER_BAR) {
            $view->content->push($this->apex->component('IndexHeaderBar'));
        }

        $this->renderIntro($view);

        $menuId = (string)$this->context->location->getLiteralPathString();
        $menuId = dirname($menuId).'/'.ucfirst($this->context->location->getRawNode());
        $view->content->addBlockMenu($menuId);

        return $view;
    }

    protected function renderIntro($view)
    {
    }
}
