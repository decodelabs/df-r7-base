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
    const IS_PRIVATE = false;

    const JOURNAL = true;
    const JOURNAL_WEEKS = 10; // weeks

    protected $_defaultToAddress = null;
    protected $_templateType;
    protected $_journalName;
    protected $_journalObjectId1;
    protected $_journalObjectId2;
    protected $_isPrivate = false;

    public function __construct(arch\IContext $context, array $args=null) {
        $this->context = $context;

        if(empty($args)) {
            $args = [];
        }

        $this->_isPrivate = static::IS_PRIVATE;
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
            return $this->context->apex->view($this->getName().'.html');
        } catch(\Exception $e) {
            $this->_templateType = 'notification';
            return $this->context->apex->view($this->getName().'.notification');
        }
    }

    public function isPrivate($flag=null) {
        if($flag !== null) {
            $this->_isPrivate = (bool)$flag;
            return $this;
        }

        return $this->_isPrivate;
    }

    public function getDescription() {
        $output = static::DESCRIPTION;

        if(empty($output)) {
            $output = $this->context->format->name($this->getName());
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
                $view->shouldRenderBase(false);

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
        return $this->_toNotification($to, $from);
    }

    public function toPreviewNotification($to=null, $from=null) {
        $this->renderPreview();
        return $this->_toNotification($to, $from);
    }

    protected function _toNotification($to=null, $from=null) {
        if($to === null) {
            $to = $this->getDefaultToAddress();
        }

        $notification = $this->view->toNotification($to, $from);

        if($this->_isPrivate) {
            $notification->isPrivate(true);
        }

        if($this->shouldJournal()) {
            $notification->shouldJournal(true);
            $notification->setJournalName($this->getJournalName());
            $notification->setJournalDuration($this->getJournalDuration());
            $notification->setJournalObjectId1($this->getJournalObjectId1());
            $notification->setJournalObjectId2($this->getJournalObjectId2());
        }

        return $notification;
    }


// Journal
    public function setJournalName($name) {
        $this->_journalName = $name;
        return $this;
    }

    public function getJournalName() {
        if($this->_journalName === null) {
            $this->_journalName = $this->_getDefaultJournalName();
        }

        return $this->_journalName;
    }

    protected function _getDefaultJournalName() {
        $output = '~'.$this->context->location->getDirectoryLocation();

        if(0 === strpos($output, '~mail/')) {
            $output = substr($output, 6);
        }

        $name = $this->getName();

        if(false !== strpos($name, '/')) {
            $output .= '#';
        }

        $output .= '/'.$name;
        return $output;
    }

    public function getJournalDuration() {
        $weeks = (int)static::JOURNAL_WEEKS;

        if($weeks <= 0) {
            $weeks = 52;
        }

        return core\time\Duration::fromWeeks($weeks);
    }

    public function setJournalObjectId1($id) {
        $this->_journalObjectId1 = $id;
        return $this;
    }

    public function getJournalObjectId1() {
        return $this->_journalObjectId1;
    }

    public function setJournalObjectId2($id) {
        $this->_journalObjectId2 = $id;
        return $this;
    }

    public function getJournalObjectId2() {
        return $this->_journalObjectId2;
    }


    public function shouldJournal() {
        return (bool)static::JOURNAL;
    }
}