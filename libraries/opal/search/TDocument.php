<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

trait TDocument
{
    protected $_id;
    protected $_values = [];
    protected $_boosts = [];

    public function __construct(string $id=null, array $values=null)
    {
        $this->setId($id);

        if ($values !== null) {
            $this->setValues($values);
        }
    }

    public function setId(?string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function setValues(array $values)
    {
        foreach ($values as $key => $value) {
            $boost = 1;

            if (is_array($value)) {
                if (isset($value['boost'])) {
                    $boost = (float)$value['boost'];
                } elseif (count($value) > 1) {
                    $boost = (float)array_pop($value);
                }

                if (isset($value['value'])) {
                    $value = $value['value'];
                } else {
                    $value = array_shift($value);
                }
            }

            $this->setValue($key, $value, $boost);
        }

        return $this;
    }

    public function getValues()
    {
        return $this->_values;
    }

    public function getPreparedValues()
    {
        $output = [];

        foreach ($this->_values as $key => $value) {
            if (is_object($value)) {
                $value = (string)$value;
            }

            $output[$key] = $value;
        }

        return $output;
    }

    public function setValue($key, $value, $boost=null)
    {
        $this->_values[$key] = $value;

        if ($boost !== null) {
            $this->setBoost($key, $boost);
        }

        return $this;
    }

    public function getValue($key)
    {
        if (isset($this->_values[$key])) {
            return $this->_values[$key];
        }

        return null;
    }

    public function setBoost($key, $boost)
    {
        $this->_boosts[$key] = (float)$boost;
        return $this;
    }

    public function getBoost($key)
    {
        if (isset($this->_boosts[$key])) {
            return $this->_boosts[$key];
        } else {
            return 1.0;
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $values = [];

        foreach ($this->_values as $key => $value) {
            $boost = $this->getBoost($key);
            $valueKey = $key;

            if ($boost != 1.0) {
                $valueKey .= ' ('.$boost.')';
            }

            $values[$valueKey] = $value;
        }

        $entity
            ->setProperties([
                '*id' => $inspector($this->_id)
            ])
            ->setValues($inspector->inspectList($values));
    }
}
