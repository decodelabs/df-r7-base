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
use df\user;
use df\opal;

abstract class RecordLink extends Base implements aura\html\widget\IWidgetProxy {

    use user\TAccessControlled;
    use core\constraint\TDisableable;
    use core\constraint\TNullable;

    const DEFAULT_MISSING_MESSAGE = 'not found';

    protected $_icon = 'item';
    protected $_disposition = 'transitive';
    protected $_note;
    protected $_maxLength;
    protected $_missingMessage;
    protected $_node;
    protected $_redirectFrom;
    protected $_redirectTo;
    protected $_name;
    protected $_matchRequest;
    protected $_record;

    protected function init($record=null, $name=null, $match=null) {
        if($record) {
            $this->setRecord($record);
        }

        if($name !== null) {
            $this->setName($name);
        }

        if($match !== null) {
            $this->setMatchRequest($match);
        }
    }

// Record
    public function setRecord($record) {
        if($record instanceof opal\record\IPrimaryKeySet
        && $record->isNull()) {
            $record = null;
        }

        if(is_scalar($record)) {
            $record = ['id' => $record];
        }

        $this->_record = $record;
        return $this;
    }

    public function getRecord() {
        return $this->_record;
    }

// Icon
    public function setIcon(string $icon=null) {
        $this->_icon = $icon;
        return $this;
    }

    public function getIcon() {
        return $this->_icon;
    }

// Disposition
    public function setDisposition($disposition) {
        $this->_disposition = $disposition;
        return $this;
    }

    public function getDisposition() {
        return $this->_disposition;
    }

// Note
    public function setNote($note) {
        $this->_note = $note;
        return $this;
    }

    public function getNote() {
        return $this->_note;
    }

// Missing message
    public function setMissingMessage($message) {
        $this->_missingMessage = $message;
        return $this;
    }

    public function getMissingMessage() {
        if(empty($this->_missingMessage)) {
            return static::DEFAULT_MISSING_MESSAGE;
        }

        return $this->_missingMessage;
    }

// Max length
    public function setMaxLength($length) {
        if(!$length) {
            $length = null;
        } else {
            $length = (int)$length;
        }

        $this->_maxLength = $length;
        return $this;
    }

    public function getMaxLength() {
        return $this->_maxLength;
    }

// Match
    public function setMatchRequest($request) {
        $this->_matchRequest = $request;
        return $this;
    }

    public function getMatchRequest() {
        return $this->_matchRequest;
    }

// Node
    public function setNode($node) {
        switch($node) {
            case 'edit':
                $this->setIcon('edit');
                $this->setDisposition('operative');

                if($this->_redirectFrom === null) {
                    $this->setRedirectFrom(true);
                }

                if(!$this->_name) {
                    $this->setName($this->_('Edit'));
                }

                break;

            case 'delete':
                $this->setIcon('delete');
                $this->setDisposition('negative');

                if($this->_redirectFrom === null) {
                    $this->setRedirectFrom(true);
                }

                if(!$this->_name) {
                    $this->setName($this->_('Delete'));
                }

                break;
        }

        $this->_node = $node;
        return $this;
    }

    public function getNode() {
        return $this->_node;
    }


// Name
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }

// Redirect
    public function setRedirectFrom($rf) {
        $this->_redirectFrom = $rf;
        return $this;
    }

    public function getRedirectFrom() {
        return $this->_redirectFrom;
    }

    public function setRedirectTo($rt) {
        if(is_string($rt)) {
            $rt = $this->context->uri->backRequest($rt);
        }

        $this->_redirectTo = $rt;
        return $this;
    }

    public function getRedirectTo() {
        return $this->_redirectTo;
    }

// Render
    public function toWidget(): aura\html\widget\IWidget {
        return $this->render();
    }

    protected function _execute() {
        if($this->_record === null && $this->_isNullable) {
            return null;
        }

        $id = null;

        if($this->_record) {
            $id = $this->_getRecordId();
        }

        if(!$this->_record || $id === null) {
            $message = $this->_missingMessage;

            if(empty($message)) {
                $message = $this->_(static::DEFAULT_MISSING_MESSAGE);
            }

            return $this->html->link('#', $message)
                ->isDisabled(true)
                ->setIcon('error')
                ->addClass('error');
        }

        if($this->_name === null) {
            try {
                $name = $this->_getRecordName();
            } catch(\Throwable $e) {
                $name = $id;
            }
        } else {
            $name = $this->_name;
        }

        $url = $this->_getRecordUrl($id);

        if($url !== null) {
            if(!$this->_redirectFrom) {
                if($this->_disposition == 'positive' || $this->_disposition == 'negative' || $this->_disposition == 'operative') {
                    $this->_redirectFrom = true;
                }
            }

            $url = $this->uri->__invoke($url, $this->_redirectFrom, $this->_redirectTo, true);

            if($url instanceof arch\IRequest && $this->_node) {
                $url->setNode($this->_node);
            }
        }

        $title = null;

        if($this->_maxLength && is_string($name)) {
            if($title === null) {
                $title = $name;
            }

            $name = $this->view->format->shorten($name, $this->_maxLength);
        }

        $name = $this->_decorateBody($name);

        $output = $this->html->link($url, $name, $this->_matchRequest)
            //->shouldCheckAccess((bool)$this->_node)
            ->setIcon($this->_icon)
            ->setDisposition($this->_disposition)
            ->setNote($this->_note)
            ->setTitle($title)
            ->addAccessLocks($this->_accessLocks)
            ->isDisabled($this->_isDisabled);

        if($this->_node && $this->_record instanceof user\IAccessLock) {
            switch($this->_node) {
                case 'edit':
                case 'delete':
                    $output->addAccessLock($this->_record->getActionLock($this->_node));
                    break;

                default:
                    $output->addAccessLock($this->_record->getActionLock('access'));
                    break;
            }
        }

        $this->_decorate($output);
        return $output;
    }

    protected function _getRecordId() {
        return $this->_record['id'];
    }

    protected function _getRecordName() {
        if(isset($this->_record['name'])) {
            return $this->_record['name'];
        } else {
            return '???';
        }
    }

    protected function _decorateBody($name) {
        return $name;
    }

    protected function _decorate($link) {}

    abstract protected function _getRecordUrl($id);
}
