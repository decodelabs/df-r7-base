<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu;

use df;
use df\core;
use df\arch;

class Base implements IMenu, core\IDumpable {
    
    use arch\TContextAware;
    
    const DEFAULT_SOURCE = 'Directory';
    
    protected $_id;
    protected $_delegates = null;
    
    public static function loadAll(arch\IContext $context, array $whiteList=null) {
        $output = array();
        $sources = arch\menu\source\Base::loadAll($context);
        
        if($whiteList !== null) {
            $temp = $whiteList;
            $whiteList = array();
            
            foreach($temp as $id) {
                $id = self::normalizeId($id);
                $sourceName = $id->getScheme();
                
                if(!isset($whiteList[$sourceName])) {
                    $whiteList[$sourceName] = array();
                }
                
                $whiteList[$sourceName][] = $id;
            }
        }
        
        foreach($sources as $source) {
            $sourceWhiteList = null;
            $sourceName = $source->getName();
            
            if($whiteList !== null) {
                if(isset($whiteList[$sourceName])) {
                    $sourceWhiteList = $whiteList[$sourceName];
                } else {
                    $sourceWhiteList = array();
                }
            }
            
            $output = array_merge($output, $source->loadAllMenues($context, $sourceWhiteList));
        }
        
        return $output;
    }

    public static function factory(arch\IContext $context, $id) {
        if($id instanceof IMenu) {
            return $id;
        }
        
        $id = self::normalizeId($id);
        $source = arch\menu\source\Base::factory($context, $id->getScheme());
        $cache = Cache::getInstance($context->getApplication());
        $cacheId = md5($id);

        $cache->clear();

        if(!isset($cache->{$cacheId})
        || null === ($output = $cache->{$cacheId})) {
            $cache->{$cacheId} = $output = $source->loadMenu($id);
        }
        
        return $output;
    }
    
    public static function clearCacheFor(arch\IContext $context, $id) {
        $id = self::normalizeId($id);
        $cache = Cache::getInstance($context->getApplication());
        $cacheId = md5($id);
        
        unset($cache->{$cacheId});
    }
    
    public static function clearCache(arch\IContext $context) {
        $cache = Cache::getInstance($context->getApplication());
        $cache->clear();
    }
    
    public static function normalizeId($id) {
        if($id instanceof IMenu) {
            return $id->getId();
        }
        
        if(!$id instanceof core\uri\IUrl) {
            $id = core\uri\Url::factory($id);
        }
        
        if(!$id->hasScheme()) {
            $id->setScheme(self::DEFAULT_SOURCE);
        } else {
            $id->setScheme(ucfirst($id->getScheme()));
        }
        
        return $id;
    }
    
    public function __construct(arch\IContext $context, $id) {
        $this->_context = $context;
        $this->_id = self::normalizeId($id);
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function getDisplayName() {
        return core\string\Manipulator::formatName($this->_id->getPath()->getLast());
    }
    
    public function getSource() {
        return arch\menu\source\Base::factory($this->_context, $this->getSourceId());
    }
    
    public function getSourceId() {
        return $this->_id->getScheme();
    }
    
    
// Delegates
    public function initDelegates() {
        if(!is_array($this->_delegates)) {
            $this->_delegates = array();
            $this->_setupDelegates();
        }
        
        return $this;
    }
    
    protected function _setupDelegates() {}
    
    public function addDelegate(IMenu $menu) {
        $this->initDelegates();
        $this->_delegates[(string)$menu->getId()] = $menu;
        return $this;
    }
    
    public function getDelegates() {
        $this->initDelegates();
        return $this->_delegates;
    }
    
// Entries
    public function generateEntries(IEntryList $entryList=null) {
        $this->initDelegates();
        
        if($isRoot = $entryList === null) {
            $entryList = new EntryList();
        }
        
        if($entryList->hasMenu($this)) {
            throw new RecursionException(
                'You cannot nest menus within themselves'
            );
        }
        
        $entryList->registerMenu($this);
        $this->_createEntries($entryList);
        
        $config = Config::getInstance();
        $config->createEntries($this, $entryList);
        
        foreach($this->_delegates as $delegate) {
            if(!$delegate instanceof IMenu) {
                $this->clearCache();
                continue;
            }
            
            $delegate->generateEntries($entryList);
        }
        
        return $entryList;
    }
    
    protected function _createEntries(IEntryList $entryList) {}
    
    
// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'delegates' => $this->_delegates
        ];
    }
}
