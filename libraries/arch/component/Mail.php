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

abstract class Mail extends Base implements arch\IMailComponent {
    
    const DESCRIPTION = null;

    public function __construct(arch\IContext $context, array $args=null) {
        $this->_context = $context;

        if(empty($args)) {
            $args = [];
        }

        $this->_componentArgs = $args;
        $this->setRenderTarget($view = $this->_loadView());
        $this->view = $view;

        if(method_exists($this, '_init')) {
            call_user_func_array([$this, '_init'], $args);
        }
    }

    protected function _loadView() {
        return $this->_context->aura->getView($this->getName().'.html');
    }


    public function getDescription() {
        $output = static::DESCRIPTION;

        if(empty($output)) {
            $output = $this->_context->format->name($this->getName());
        }

        return $output;
    }

// Renderable
    public function toString() {
        return $this->render();
    }

    public function render() {
        $this->view = $this->getRenderTarget()->getView();

        if(method_exists($this, '_prepare')) {
            call_user_func_array([$this, '_prepare'], $this->_componentArgs);
        }

        return $this->_normalizeView($this->view);
    }

    public function renderPreview() {
        $this->view = $this->getRenderTarget()->getView();

        if(method_exists($this, '_preparePreview')) {
            call_user_func_array([$this, '_preparePreview'], $this->_componentArgs);
        }

        return $this->_normalizeView($this->view);
    }

    protected function _normalizeView(aura\view\IView $view) {
        if(!$view->hasTheme()) {
            $themeConfig = aura\theme\Config::getInstance();
            $view->setTheme($themeConfig->getThemeIdFor('front'));
        }

        return $view;
    }

    public function toResponse() {
        return $this->render();
    }

    public function toNotification($to=null, $from=null) {
        return $this->render()->toNotification($to, $from);
    }

    public function toPreviewNotification($to=null, $from=null) {
        return $this->renderPreview()->toNotification($to, $from);
    }
}