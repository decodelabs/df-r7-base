<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\menu;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;

use DecodeLabs\R7\Legacy;
use df\arch;
use df\core;

class Base implements IMenu, \Serializable
{
    use core\TContextAware;

    public const DEFAULT_SOURCE = 'Directory';

    protected $_id;
    protected $_subId;
    protected $_delegates = null;

    public static function loadAll(arch\IContext $context, array $whiteList = null)
    {
        $output = [];
        $sources = arch\navigation\menu\source\Base::loadAll($context);

        if ($whiteList !== null) {
            $temp = $whiteList;
            $whiteList = [];

            foreach ($temp as $id) {
                $id = self::normalizeId($id);
                $sourceName = $id->getScheme();

                if (!isset($whiteList[$sourceName])) {
                    $whiteList[$sourceName] = [];
                }

                $whiteList[$sourceName][] = $id;
            }
        }

        foreach ($sources as $source) {
            if (!$source instanceof IListableSource) {
                continue;
            }

            $sourceWhiteList = null;
            $sourceName = $source->getName();

            if ($whiteList !== null) {
                if (isset($whiteList[$sourceName])) {
                    $sourceWhiteList = $whiteList[$sourceName];
                } else {
                    $sourceWhiteList = [];
                }
            }

            $output = array_merge($output, $source->loadAllMenus($sourceWhiteList));
        }

        return $output;
    }

    public static function loadList(arch\IContext $context, array $ids)
    {
        $output = [];

        foreach ($ids as $id) {
            try {
                $output[$id] = self::factory($context, $id);
            } catch (\Throwable $e) {
            }
        }

        return $output;
    }

    public static function factory(arch\IContext $context, $id): IMenu
    {
        if ($id instanceof IMenu) {
            return $id;
        }

        $id = self::normalizeId($id);
        $source = arch\navigation\menu\source\Base::factory($context, $id->getScheme());
        $cache = Cache::getInstance();

        //$cacheId = md5($id);
        $cacheId = (string)$id;

        //$cache->clear();

        if (!$cache->has($cacheId)
        || null === ($output = $cache->get($cacheId))) {
            $output = $source->loadMenu($id);
            $cache->set($cacheId, $output);
        }

        return $output;
    }

    public static function clearCacheFor(arch\IContext $context, $id)
    {
        $id = self::normalizeId($id);
        $cache = Cache::getInstance();

        //$cacheId = md5($id);
        $cacheId = (string)$id;

        $cache->remove($cacheId);
    }

    public static function clearCache(arch\IContext $context)
    {
        $cache = Cache::getInstance();
        $cache->clear();
    }

    public static function normalizeId($id): core\uri\IUrl
    {
        if ($id instanceof IMenu) {
            return $id->getId();
        }

        if (!$id instanceof core\uri\IUrl) {
            $id = core\uri\Url::factory($id);
        }

        if (!$id->hasScheme()) {
            $id->setScheme(self::DEFAULT_SOURCE);
        } else {
            $id->setScheme(ucfirst($id->getScheme()));
        }

        return $id;
    }

    public function __construct(arch\IContext $context, string $id)
    {
        $this->context = $context;
        $this->_id = self::normalizeId($id);
    }

    public function serialize()
    {
        return serialize($this->__serialize());
    }

    public function __serialize(): array
    {
        return [
            'id' => $this->_id,
            'subId' => $this->_subId,
            'delegates' => $this->_delegates,
            'context' => $this->context
        ];
    }

    public function unserialize(string $data): void
    {
        /** @var array $unserialized */
        $unserialized = unserialize($data);
        $this->__unserialize($unserialized);
    }

    public function __unserialize(array $data): void
    {
        $this->_id = $data['id'];
        $this->_subId = $data['subId'];
        $this->_delegates = $data['delegates'];

        if (isset($data['context'])) {
            $this->context = $data['context'];
        } else {
            $this->context = Legacy::getContext();
        }
    }

    public function getId(): core\uri\IUrl
    {
        return $this->_id;
    }

    public function setSubId($id)
    {
        $this->_subId = $id;
        return $this;
    }

    public function getSubId()
    {
        return $this->_subId;
    }

    public function getDisplayName(): string
    {
        $parts = explode('_', $this->_id->getPath()->getLast());
        $output = Dictum::name(array_shift($parts));

        if (!empty($parts)) {
            $output .= ' (' . Dictum::name(array_shift($parts)) . ')';
        }

        return $output;
    }

    public function getSource()
    {
        return arch\navigation\menu\source\Base::factory($this->context, $this->getSourceId());
    }

    public function getSourceId()
    {
        return $this->_id->getScheme();
    }


    // Delegates
    public function initDelegates()
    {
        if (!is_array($this->_delegates)) {
            $this->_delegates = [];
            $this->loadDelegates();
        }

        return $this;
    }

    protected function loadDelegates(): void
    {
    }

    public function addDelegate(IMenu $menu)
    {
        $this->initDelegates();
        $this->_delegates[(string)$menu->getId()] = $menu;
        return $this;
    }

    public function getDelegates()
    {
        $this->initDelegates();
        return $this->_delegates;
    }

    // Entries
    public function generateEntries(arch\navigation\IEntryList $entryList = null): arch\navigation\IEntryList
    {
        $this->initDelegates();
        $isRoot = ($entryList === null);

        if ($entryList === null) {
            $entryList = new EntryList();
        }

        if ($entryList instanceof IEntryList) {
            if ($entryList->hasMenu($this)) {
                throw Exceptional::Recursion(
                    'You cannot nest menus within themselves'
                );
            }

            $entryList->registerMenu($this);
        }

        $this->createEntries($entryList);

        foreach ($this->_delegates as $delegate) {
            if (!$delegate instanceof IMenu) {
                $this->clearCache($this->context);
                continue;
            }

            $delegate->generateEntries($entryList);
        }

        return $entryList;
    }

    protected function createEntries(/*arch\navigation\IEntryList*/ $entryList)
    {
    }
}
