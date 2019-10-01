<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Attachment implements opal\query\IAttachmentField, core\IDumpable
{
    use opal\query\TField;

    protected $_name;
    protected $_attachment;

    public function __construct(string $name, opal\query\IAttachQuery $attachment)
    {
        $this->_name = $name;
        $this->_attachment = $attachment;
    }

    public function getSource()
    {
        return $this->_attachment->getSource();
    }

    public function getSourceAlias()
    {
        return $this->_attachment->getSourceAlias();
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function getQualifiedName()
    {
        return $this->_attachment->getParentQuery()->getSourceAlias().'.'.$this->_name;
    }

    public function setAlias($alias)
    {
        $this->_name = $alias;
        return $this;
    }

    public function getAlias()
    {
        return $this->_name;
    }

    public function hasDiscreetAlias()
    {
        return false;
    }

    public function dereference()
    {
        return [$this];
    }

    public function isOutputField()
    {
        return true;
    }

    public function getAttachment()
    {
        return $this->_attachment;
    }

    public function shouldBeProcessed()
    {
        if ($this->_attachment instanceof opal\query\ISelectQuery && !$this->_attachment->isPopulate()) {
            return false;
        }

        return true;
    }

    public function rewriteAsDerived(opal\query\ISource $source)
    {
        Glitch::incomplete($source);
    }

    public function toString(): string
    {
        return 'attach('.$this->getQualifiedName().')';
    }
}
