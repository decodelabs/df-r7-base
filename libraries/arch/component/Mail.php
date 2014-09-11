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
use df\flow;

abstract class Mail extends Base implements arch\IMailComponent {
    
    const DESCRIPTION = null;

    protected $_defaultToAddress = null;
    protected $_templateType;

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
        try {
            $this->_templateType = 'html';
            return $this->_context->aura->getView($this->getName().'.html');
        } catch(\Exception $e) {
            $this->_templateType = 'notification';
            return $this->_context->aura->getView($this->getName().'.notification');
        }
    }


    public function getDescription() {
        $output = static::DESCRIPTION;

        if(empty($output)) {
            $output = $this->_context->format->name($this->getName());
        }

        return $output;
    }

    public function getTemplateType() {
        return $this->_templateType;
    }

// Default to
    public function setDefaultToAddress($address, $name=null) {
        if($address !== null) {
            $address = flow\mail\Address::factory($address, $name);
        }

        $this->_defaultToAddress = $address;
        return $this;
    }

    public function getDefaultToAddress() {
        return $this->_defaultToAddress;
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

        if(!$this->_defaultToAddress) {
            $this->setDefaultToAddress($this->user->client->getEmail());
        }

        return $this->_normalizeView($this->view);
    }

    protected function _normalizeView(aura\view\IView $view) {
        switch($this->_templateType) {
            case 'html':
                if(!$view->hasTheme()) {
                    $themeConfig = aura\theme\Config::getInstance();
                    $view->setTheme($themeConfig->getThemeIdFor('front'));
                }
                break;
        }

        return $view;
    }

    public function toResponse() {
        return $this->render();
    }

    public function toNotification($to=null, $from=null) {
        $this->render();

        if($to === null) {
            $to = $this->getDefaultToAddress();
        }

        return $this->view->toNotification($to, $from);
    }

    public function toPreviewNotification($to=null, $from=null) {
        $this->renderPreview();

        if($to === null) {
            $to = $this->getDefaultToAddress();
        }

        return $this->view->toNotification($to, $from);
    }
}