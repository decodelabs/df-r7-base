<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\mail\mailchimp3\filter;

use df\core;
use df\spur;

class Base implements spur\mail\mailchimp3\IFilter
{
    public const KEY_NAME = null;

    use spur\TFilter;

    protected $_offset = 0;
    protected $_fields = [];
    protected $_excludeFields = [];
    protected $_includeLinks = false;


    public function getKeyName(): ?string
    {
        return static::KEY_NAME;
    }

    public function setOffset(int $offset)
    {
        $this->_offset = $offset;
        return $this;
    }

    public function getOffset(): int
    {
        return $this->_offset;
    }


    public function setFields(string ...$fields)
    {
        $this->_fields = $fields;
        return $this;
    }

    public function addFields(string ...$fields)
    {
        $this->_excludeFields = core\collection\Util::flatten(
            array_merge($this->_fields, $fields),
            true,
            true
        );

        return $this;
    }

    public function getFields(): array
    {
        return $this->_fields;
    }


    public function setExcludeFields(string ...$fields)
    {
        $this->_excludeFields = $fields;
        return $this;
    }

    public function addExcludeFields(string ...$fields)
    {
        $this->_excludeFields = core\collection\Util::flatten(
            array_merge($this->_excludeFields, $fields),
            true,
            true
        );

        return $this;
    }

    public function getExcludeFields(): array
    {
        return $this->_excludeFields;
    }

    public function shouldIncludeLinks(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_includeLinks = $flag;
            return $this;
        }

        return $this->_includeLinks;
    }




    public function toArray(): array
    {
        $output = [
            'offset' => $this->_offset
        ];

        if ($this->_limit) {
            $output['count'] = $this->_limit;
        } else {
            $this->_limit = 10;
        }

        if (!empty($this->_fields)) {
            $output['fields'] = $this->_normalizeFields($this->_fields);
        } else {
            $output['exclude_fields'] = $this->_normalizeFields(
                $this->_excludeFields,
                $this->_includeLinks ? null : ['_links', static::KEY_NAME . '._links']
            );
        }

        return $output;
    }

    protected function _normalizeFields(array $fields, array $extra = null): string
    {
        $fields = array_map(function ($in) {
            return static::KEY_NAME . '.' . $in;
        }, $fields);

        if ($extra) {
            $fields = array_unique(array_merge($extra, $fields));
        }

        return implode(',', $fields);
    }
}
