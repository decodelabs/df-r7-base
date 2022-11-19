<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu;

use df\arch;
use df\core;

class Config extends core\Config implements IConfig
{
    public const ID = 'menus';
    public const STORE_IN_MEMORY = false;

    public function getDefaultValues(): array
    {
        return [];
    }

    public function createEntries(IMenu $menu, IEntryList $entryList)
    {
        $id = (string)$menu->getId();

        if ($this->values->isEmpty()) {
            return $this;
        }

        $context = $menu->getContext();

        foreach ($this->values->{$id}->delegates as $delegate) {
            try {
                $menu->addDelegate(Base::factory($context, (string)$delegate));
            } catch (Exception $e) {
                continue;
            }
        }

        foreach ($this->values->{$id}->entries as $entry) {
            $entryList->addEntry(
                arch\navigation\entry\Base::fromArray($entry->toArray())
            );
        }

        return $this;
    }

    public function setDelegatesFor($id, array $delegates)
    {
        $id = (string)Base::normalizeId($id);
        $this->values->{$id}['delegates'] = $delegates;

        return $this;
    }

    public function setEntriesFor($id, array $entries)
    {
        $id = (string)Base::normalizeId($id);
        $data = [];

        foreach ($entries as $entry) {
            try {
                if (!$entry instanceof arch\navigation\IEntry) {
                    if (is_array($entry)) {
                        $entry = arch\navigation\entry\Base::fromArray($entry);
                    } else {
                        continue;
                    }
                }

                $data[] = $entry->toArray();
            } catch (arch\navigation\Exception $e) {
                continue;
            }
        }

        $this->values->{$id}->entries = $data;

        return $this;
    }

    public function getSettingsFor($id)
    {
        $id = (string)Base::normalizeId($id);
        return $this->values->{$id};
    }
}
