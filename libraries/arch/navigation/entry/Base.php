<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\entry;

use DecodeLabs\Exceptional;

use df\arch;

abstract class Base implements arch\navigation\IEntry
{
    protected $_id;
    protected $_weight = 0;

    public static function fromArray(array $entry): arch\navigation\IEntry
    {
        $type = 'None';

        if (isset($entry['type'])) {
            $type = $entry['type'];
        }

        if (!$class = self::_getEntryClass($type)) {
            throw Exceptional::NotFound(
                'Entry type ' . $type . ' could not be found'
            );
        }

        if (!isset($entry['id'])) {
            $entry['id'] = null;
        }

        if (!isset($entry['weight'])) {
            $entry['weight'] = 0;
        }

        return $class::_fromArray($entry);
    }

    protected static function _fromArray(array $entry): arch\navigation\IEntry
    {
        return (new static())->setId($entry['id'])->setWeight($entry['weight']);
    }


    public static function factory($type, ...$args): arch\navigation\IEntry
    {
        if (!$class = self::_getEntryClass($type)) {
            throw Exceptional::NotFound(
                'Entry type ' . $type . ' could not be found'
            );
        }

        $output = (new \ReflectionClass($class))->newInstanceArgs($args);

        if (!$output instanceof arch\navigation\IEntry) {
            throw Exceptional::Logic(
                'Entry class does not implement IEntry',
                null,
                $output
            );
        }

        return $output;
    }


    protected static function _getEntryClass($type)
    {
        $class = 'df\\arch\\navigation\\entry\\' . ucfirst($type);

        if (!class_exists($class)) {
            return null;
        }

        return $class;
    }


    public function getType()
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function setId(?string $id)
    {
        if (empty($id)) {
            $id = null;
        }

        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }

    public function setWeight($weight)
    {
        $this->_weight = (float)$weight;
        return $this;
    }

    public function getWeight()
    {
        return $this->_weight;
    }
}
