<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\menu;

use DecodeLabs\Exceptional;

use df\arch;

class Dynamic extends Base
{
    use arch\navigation\TEntryGenerator;

    protected $_recordId;
    protected $_displayName;
    protected $_entries = [];


    public function __serialize(): array
    {
        return array_merge(
            parent::__serialize(),
            [
                'recordId' => $this->_recordId,
                'displayName' => $this->_displayName,
                'entries' => $this->_entries
            ]
        );
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);

        $this->_recordId = $data['recordId'];
        $this->_displayName = $data['displayName'];
        $this->_entries = $data['entries'];
    }

    public function setRecordId($id)
    {
        $this->_recordId = $id;
        return $this;
    }

    public function getRecordId()
    {
        return $this->_recordId;
    }

    public function setDisplayName($name)
    {
        $this->_displayName = $name;
        return $this;
    }

    public function getDisplayName(): string
    {
        if ($this->_displayName !== null) {
            return $this->_displayName;
        } else {
            return parent::getDisplayName();
        }
    }

    public function setEntries(array $entries)
    {
        $this->_entries = [];
        return $this->addEntries($entries);
    }

    public function addEntries(array $entries)
    {
        foreach ($entries as $entry) {
            $this->addEntry($entry);
        }

        return $this;
    }

    public function addEntry($entry)
    {
        if (!$entry instanceof arch\navigation\IEntry) {
            if (is_array($entry)) {
                $entry = arch\navigation\entry\Base::fromArray($entry);
            } else {
                throw Exceptional::InvalidArgument([
                    'message' => 'Invalid entry definition',
                    'data' => $entry
                ]);
            }
        }

        $this->_entries[] = $entry;
        return $this;
    }

    public function getEntries()
    {
        return $this->_entries;
    }

    protected function createEntries($entryList)
    {
        $entryList->addEntries($this->_entries);
    }
}
