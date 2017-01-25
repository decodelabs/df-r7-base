<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\entry;

use df;
use df\core;
use df\arch;

class Link extends Base implements arch\navigation\ILink {

    use arch\navigation\TSharedLinkComponents;

    protected $_body;
    protected $_icon;
    protected $_checkMatch = false;
    protected $_newWindow = false;
    protected $_disposition;
    protected $_class;

    protected static function _fromArray(array $entry) {
        $tree = new core\collection\Tree($entry);

        return (new self(
                $tree['uri'],
                $tree['body'],
                $tree['icon']
            ))
            ->setId($tree['id'])
            ->setDisposition($tree['disposition'])
            ->shouldOpenInNewWindow((bool)$tree['newWindow'])
            ->setWeight($tree['weight'])
            ->setClass($tree['class'])
            ->_setSharedLinkComponentData($tree);
    }

    public function __construct($uri, $body, $icon=null) {
        $this->setUri($uri);
        $this->setBody($body);
        $this->setIcon($icon);
    }

    public function toArray(): array {
        return array_merge([
            'type' => 'Link',
            'id' => $this->getId(),
            'weight' => $this->getWeight(),
            'body' => $this->_body,
            'icon' => $this->_icon,
            'newWindow' => $this->_newWindow,
            'disposition' => $this->_disposition,
            'class' => $this->_class
        ], $this->_getSharedLinkComponentData());
    }

    public function getId() {
        if($this->_id === null) {
            return $this->_id = 'link-'.md5((string)$this->getUri());
        }

        return parent::getId();
    }

    public function setBody($body) {
        $this->_body = $body;
        return $this;
    }

    public function getBody() {
        return $this->_body;
    }

    public function setIcon(string $icon=null) {
        $this->_icon = $icon;
        return $this;
    }

    public function getIcon() {
        return $this->_icon;
    }

    public function shouldCheckMatch(bool $flag=null) {
        if($flag !== null) {
            $this->_checkMatch = $flag;
            return $this;
        }

        return $this->_checkMatch;
    }

    public function shouldOpenInNewWindow(bool $flag=null) {
        if($flag !== null) {
            $this->_newWindow = $flag;
            return $this;
        }

        return $this->_newWindow;
    }

    public function setDisposition($disposition) {
        $this->_disposition = $disposition;
        return $this;
    }

    public function getDisposition() {
        return $this->_disposition;
    }

    public function setClass($class) {
        $this->_class = $class;
        return $this;
    }

    public function getClass() {
        return $this->_class;
    }
}
