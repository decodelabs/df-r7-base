<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow;

class FlashMessage implements IFlashMessage, \Serializable
{
    protected $_id;
    protected $_isDisplayed = false;
    protected $_displayCount = 0;
    protected $_type = IFlashMessage::INFO;
    protected $_message;
    protected $_description;
    protected $_link;
    protected $_linkText;
    protected $_linkNewWindow = false;

    public static function factory($id, $message = null, $type = null)
    {
        if ($id instanceof IFlashMessage) {
            return $id;
        } elseif ($message instanceof IFlashMessage) {
            return $message;
        }

        return new self($id, $message, $type);
    }

    public function __construct($id, $message = null, $type = null)
    {
        if ($message === null) {
            $message = $id;
            $id = null;
        }

        if ($id === null) {
            $id = md5($message);
        }

        $this->_id = $id;
        $this->setMessage($message);

        if ($type !== null) {
            $this->setType($type);
        }
    }

    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    public function __serialize(): array
    {
        $data = [
            'id' => $this->_id,
            'dp' => $this->_isDisplayed,
            'dc' => $this->_displayCount,
            'tp' => $this->_type,
            'ms' => $this->_message
        ];

        if ($this->_description) {
            $data['ds'] = $this->_description;
        }

        if ($this->_link) {
            $data['ln'] = $this->_link;
        }

        if ($this->_linkText) {
            $data['lt'] = $this->_linkText;
        }

        if ($this->_linkNewWindow) {
            $data['lw'] = $this->_linkNewWindow;
        }

        return $data;
    }

    public function unserialize(string $data): void
    {
        $data = json_decode($data, true);
        $this->__unserialize($data);
    }

    public function __unserialize(array $data): void
    {
        $this->_id = $data['id'];
        $this->_isDisplayed = $data['dp'];
        $this->_displayCount = $data['dc'];
        $this->_type = $data['tp'];
        $this->_message = $data['ms'];

        if (isset($data['ds'])) {
            $this->_description = $data['ds'];
        }

        if (isset($data['ln'])) {
            $this->_link = $data['ln'];
        }

        if (isset($data['lt'])) {
            $this->_linkText = $data['lt'];
        }

        if (isset($data['lw'])) {
            $this->_linkNewWindow = (bool)$data['lw'];
        }
    }

    public function getId(): string
    {
        return $this->_id;
    }

    public function setType($type)
    {
        switch ($type) {
            case IFlashMessage::INFO:
            case IFlashMessage::SUCCESS:
            case IFlashMessage::ERROR:
            case IFlashMessage::WARNING:
            case IFlashMessage::DEBUG:
                break;

            default:
                $type = IFlashMessage::INFO;
                break;
        }

        $this->_type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function isDebug()
    {
        return $this->_type === IFlashMessage::DEBUG;
    }


    public function isDisplayed(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isDisplayed = $flag;
            return $this;
        }

        return $this->_isDisplayed;
    }

    public function canDisplayAgain()
    {
        return $this->_displayCount > 0;
    }

    public function resetDisplayState()
    {
        $this->_isDisplayed = false;
        $this->_displayCount--;
        return $this;
    }

    public function setDisplayCount($count)
    {
        $this->_displayCount = (int)$count;
        return $this;
    }

    public function getDisplayCount()
    {
        return $this->_displayCount;
    }

    public function setMessage($message)
    {
        $this->_message = trim((string)$message);
        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setDescription($description)
    {
        $this->_description = trim((string)$description);
        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }

    public function setLink($link, $text = null)
    {
        if ($link === null) {
            $this->_link = null;
        } else {
            $this->_link = $link;
        }

        $this->_linkText = $text;
        return $this;
    }

    public function getLink()
    {
        return $this->_link;
    }

    public function setLinkText($text)
    {
        $this->_linkText = $text;
        return $this;
    }

    public function getLinkText()
    {
        return $this->_linkText;
    }

    public function hasLink()
    {
        return $this->_link !== null;
    }

    public function clearLink()
    {
        $this->_link = null;
        $this->_linkText = null;
        return $this;
    }

    public function shouldLinkOpenInNewWindow(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_linkNewWindow = $flag;
            return $this;
        }

        return $this->_linkNewWindow;
    }
}
