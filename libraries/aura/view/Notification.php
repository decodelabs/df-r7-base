<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flow;

class Notification extends Base implements INotificationProxyView {

    use TLayoutView;

    const DEFAULT_LAYOUT = 'Default';

    protected $_subject;

    public function setSubject($subject) {
        $this->_subject = $subject;
        return $this;
    }

    public function getSubject() {
        return $this->_subject;
    }

    public function render() {
        $shouldUseLayout = $this->shouldUseLayout();
        $this->shouldUseLayout(false);
        $output = parent::render();
        $this->shouldUseLayout($shouldUseLayout);

        return $output;
    }

    public function toNotification($to=null, $from=null) {
        $content = $this->render();

        $htmlView = new Html('Html', $this->context);
        $htmlView->setContentProvider($contentContainer = new aura\view\content\WidgetContentProvider($this->context));
        $contentContainer->push($this->html->simpleTags($content));
        $htmlView->setLayout($this->getLayout());
        $htmlView->shouldUseLayout($this->shouldUseLayout());

        $htmlView->shouldRenderBase(false);

        if(!$htmlView->hasTheme()) {
            $themeConfig = aura\theme\Config::getInstance();
            $htmlView->setTheme($themeConfig->getThemeIdFor('front'));
        }

        return $htmlView->toNotification($to, $from);
    }

    public function toHtml() {
        return $this->html->simpleTags($this->render());
    }
}