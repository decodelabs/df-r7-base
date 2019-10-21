<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Wildcard implements opal\query\IWildcardField, Inspectable
{
    use opal\query\TField;

    protected $_source;
    protected $_muteFields = [];

    public function __construct(opal\query\ISource $source)
    {
        $this->_source = $source;
    }

    public function getSource()
    {
        return $this->_source;
    }

    public function getSourceAlias()
    {
        return $this->_source->getAlias();
    }

    public function getQualifiedName()
    {
        return $this->getSourceAlias().'.*';
    }

    public function getName(): string
    {
        return '*';
    }

    public function setAlias($alias)
    {
        Glitch::incomplete($alias);
        return $this;
    }

    public function getAlias()
    {
        return '*';
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

    public function rewriteAsDerived(opal\query\ISource $source)
    {
        Glitch::incomplete($source);
    }


    public function addMuteField($name, $alias=null)
    {
        $this->_muteFields[$name] = $alias;
        return $this;
    }

    public function removeMuteField($name)
    {
        unset($this->_muteFields[$name]);
        return $this;
    }

    public function getMuteFields()
    {
        return $this->_muteFields;
    }


    public function toString(): string
    {
        $output = $this->getQualifiedName();
        $mute = [];

        foreach ($this->_muteFields as $name => $alias) {
            $mute[] = '!'.$name.($alias ? ' as '.$alias : '');
        }

        if (!empty($mute)) {
            $output .= ' ('.implode(', ', $mute).')';
        }

        return $output;
    }
}
