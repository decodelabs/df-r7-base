<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;
use df\axis;
use df\opal;


// Record loader
trait TScaffold_RecordLoader {

    //const RECORD_KEY_NAME = null;
    //const RECORD_ITEM_NAME = null;

    protected $_recordAdapter;

    public function getRecordAdapter() {
        if($this->_recordAdapter) {
            return $this->_recordAdapter;
        }

        if(@static::RECORD_ADAPTER) {
            $adapter = $this->data->fetchEntity(static::RECORD_ADAPTER);

            if($adapter instanceof axis\IUnit) {
                $this->_recordAdapter = $adapter;
                return $adapter;
            }
        }

        throw new LogicException(
            'Unabled to find a suitable adapter for record scaffold'
        );
    }

    public function getRecordKeyName() {
        if(@static::RECORD_KEY_NAME) {
            return static::RECORD_KEY_NAME;
        }

        $adapter = $this->getRecordAdapter();
        return lcfirst($adapter->getUnitName());
    }

    public function getRecordItemName() {
        if(@static::RECORD_ITEM_NAME) {
            return static::RECORD_ITEM_NAME;
        }

        return $this->getRecordKeyName();
    }
}

// Record provider
trait TScaffold_RecordDataProvider {

    //const RECORD_ID_KEY = 'id';
    //const RECORD_NAME_KEY = 'name';
    //const RECORD_URL_KEY = null;
    //const RECORD_ADAPTER = null;

    protected $_record;
    protected $_recordAction;
    protected $_recordDetailsFields = [];

    public function getRecord() {
        $this->_ensureRecord();
        return $this->_record;
    }

    public function getRecordId($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }

        return $record[$this->getRecordIdKey()];
    }

    public function getRecordName($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }

        return $record[$this->getRecordNameKey()];
    }

    public function getRecordUrl($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }

        $output = clone $this->_context->location;
        $output->setAction('details');
        $output->query->{$this->_getRecordUrlKey()} = $this->getRecordId($record);

        return $output;
    }

    protected function _ensureRecord() {
        if($this->_record) {
            return $this->_record;
        }

        $key = $this->_context->request->query[$this->_getRecordUrlKey()];
        $this->_record = $this->_loadRecord($key, $this->_recordAction);

        if(!$this->_record) {
            throw new RuntimeException('Unable to load scaffold record');
        }

        return $this->_record;
    }

    protected function _loadRecord($key, $action) {
        return $this->data->fetchForAction(
            $this->getRecordAdapter(), $key, $action
        );
    }

    public function getRecordIdKey() {
        if(@static::RECORD_ID_KEY) {
            return static::RECORD_ID_KEY;
        }

        return 'id';
    }

    public function getRecordNameKey() {
        if(@static::RECORD_NAME_KEY) {
            return static::RECORD_NAME_KEY;
        }

        return 'name';
    }

    protected function _getRecordUrlKey() {
        if(@static::RECORD_URL_KEY) {
            return static::RECORD_URL_KEY;
        }

        return $this->getRecordKeyName();
    }

    protected function _getRecordActionRequest($record, $action, array $query=null, $redirFrom=null, $redirTo=null) {
        return $this->_getActionRequest($action, [
            $this->_getRecordUrlKey() => $this->getRecordId($record)
        ], $redirFrom, $redirTo);
    }

    public function buildLinkComponent(array $args) {
        return new arch\scaffold\component\RecordLink($this, $args);
    }

    public function buildDetailsComponent(array $args) {
        if(!isset($args[0])) {
            $args[0] = [];
        }

        $args[0] = array_merge($this->_recordDetailsFields, $args[0]);
        $output = new arch\component\template\AttributeList($this->_context, $args);

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method = 'describe'.ucfirst($field).'Field';

                if(method_exists($this, $method)) {
                    $output->setField($field, function($list, $key) use($method) {
                        return $this->{$method}($list, 'details');
                    });
                }
            }
        }

        return $output;
    }

    public function getRecordOperativeLinks($record, $mode) {
        return [
            // Edit
            $this->html->link(
                    $this->_getRecordActionRequest($record, 'edit', null, true),
                    $this->_('Edit '.$this->getRecordItemName())
                )
                ->setIcon('edit'),

            // Delete
            $this->html->link(
                    $this->_getRecordActionRequest(
                        $record, 'delete', null, true,
                        $mode == 'sectionHeaderBar' ?
                            $this->_context->location->getPath()->getDirname().'/' : null
                    ),
                    $this->_('Delete '.$this->getRecordItemName())
                )
                ->setIcon('delete')
        ];
    }


    public function describeWeightField($list, $mode) {
        $list->addField('weight', $mode == 'list' ? '#' : $this->_('Order number'));
    }

    public function describeNameField($list, $mode) {
        $list->addField('name', function($item) use($mode) {
            if($mode == 'list') {
                return $this->import->component(
                        ucfirst($this->getRecordKeyName().'Link'), 
                        $this->_context->location, 
                        $item
                    )
                    ->setMaxLength(50);
            }

            return $item['name'];
        });
    }

    public function describeUrlField($list, $mode) {
        $list->addField('url', function($item) use($mode) {
            $url = $item['url'];

            if($mode == 'list') {
                $url = $this->normalizeOutputUrl($url);
                $name = $url->getDomain();
            } else {
                $name = $url;
            }

            return $this->html->link($url, $name)
                ->setIcon('link')
                ->setTarget('_blank');
        });
    }

    public function describeActionsField($list, $mode) {
        $list->addField('actions', function($item) {
            return $this->getRecordOperativeLinks($item, 'list');
        });
    }
}


// Record list provider
trait TScaffold_RecordListProvider {

    protected $_recordListFields = [];

    public function getRecordListQuery($mode, array $fields=null) {
        $output = $this->getRecordAdapter()->select($fields);
        $this->_prepareRecordListQuery($output, $mode);
        return $output;
    }

    protected function _prepareRecordListQuery(opal\query\ISelectQuery $query, $mode) {}

    public function buildListComponent(array $args) {
        if(!isset($args[0])) {
            $args[0] = [];
        }

        $args[0] = array_merge($this->_recordListFields, $args[0]);
        $output = new arch\component\template\CollectionList($this->_context, $args);

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method = 'describe'.ucfirst($field).'Field';

                if(method_exists($this, $method)) {
                    $output->setField($field, function($list, $key) use($method) {
                        return $this->{$method}($list, 'list');
                    });
                }
            }
        }

        return $output;
    }

    public function buildSelectorFormDelegate($state, $id) {
        return new arch\scaffold\delegate\SearchSelector($this, $state, $id);
    }
}






trait TScaffold_SectionProvider {

    protected $_sections = [];
    private $_sectionItemCounts = null;

    public function loadSectionAction(arch\IController $controller=null) {
        $action = $this->_context->request->getAction();

        if(isset($this->_sections[$action]) || in_array($action, $this->_sections)) {
            return new Action($this->_context, $this, function() use($action) {
                $record = null;

                if($this instanceof IRecordDataProviderScaffold) {
                    $record = $this->getRecord();
                }

                $container = $this->aura->getWidgetContainer();
                $this->view = $container->getView();

                $method = 'render'.ucfirst($action).'SectionBody';

                if(method_exists($this, $method)) {
                    $body = $this->{$method}($record);
                } else {
                    $body = null;
                }

                $container->push(
                    $this->import->component('SectionHeaderBar', $this->_context->location, $record),
                    $body
                );

                return $this->view;
            }, $controller);
        }
    }

    public function buildSectionHeaderBarComponent(array $args) {
        return (new arch\scaffold\component\HeaderBar($this, 'section', $args))
            ->setTitle(
                $this instanceof IRecordDataProviderScaffold ?
                    $this->_(
                        ucfirst($this->getRecordItemName()).': %n%',
                        ['%n%' => $this->format->shorten($this->getRecordName(), 50)]
                    ) :
                    $this->getDirectoryTitle()
            );
    }

    public function addSectionHeaderBarOperativeLinks($menu, $bar) {
        if($this instanceof IRecordDataProviderScaffold) {
            $menu->addLinks($this->getRecordOperativeLinks($this->getRecord(), 'sectionHeaderBar'));
        }
    }

    public function addSectionHeaderBarSubOperativeLinks($menu, $bar) {
        $action = $this->_context->request->getAction();
        $method = 'add'.ucfirst($action).'SectionHeaderBarSubOperativeLinks';

        if(method_exists($this, $method)) {
            $this->{$method}($menu, $bar);
        }
    }

    public function addSectionHeaderBarSectionLinks($menu, $bar) {
        if(count($this->_sections) == 1) {
            return;
        }

        $counts = $this->getSectionItemCounts();

        foreach($this->_sections as $action => $set) {
            if(!is_array($set)) {
                $action = $set;
                $set = [];
            }

            if($this instanceof IRecordDataProviderScaffold) {
                $request = $this->_getRecordActionRequest($this->getRecord(), $action);
            } else {
                $request = $this->_getActionRequest($action);
            }

            if(isset($set['name'])) {
                $name = $this->_($set['name']);
            } else {
                $name = $this->format->name($action);
            }

            if(isset($set['icon'])) {
                $icon = $set['icon'];
            } else if($action == 'details') {
                $icon = 'details';
            } else {
                $icon = null;
            }

            $link = $this->html->link($request, $name)
                ->setIcon($icon)
                ->setDisposition('informative');

            if(isset($counts[$action])) {
                $link->setNote($this->format->counterNote($counts[$action]));
            }

            $menu->addLink($link);
        }
    }

    public function getSectionItemCounts() {
        if($this->_sectionItemCounts === null) {
            $this->_sectionItemCounts = (array)$this->_fetchSectionItemCounts();
        }

        return $this->_sectionItemCounts;
    }

    protected function _fetchSectionItemCounts() {
        return [];
    }
}





// Index header bar provider
trait TScaffold_IndexHeaderBarProvider {

    public function buildIndexHeaderBarComponent(array $args=null) {
        return (new arch\scaffold\component\HeaderBar($this, 'index', $args))
            ->setTitle($this->getDirectoryTitle());
    }
}

trait TScaffold_RecordIndexHeaderBarProvider {

    public function addIndexHeaderBarOperativeLinks($menu, $bar) {
        $menu->addLinks(
            $bar->html->link(
                    $bar->uri->request($this->_getActionRequest('add'), true),
                    $this->_('Add '.$this->getRecordKeyName())
                )
                ->setIcon('add')
                ->addAccessLock($this->getRecordAdapter()->getEntityLocator()->toString().'#add')
        );
    }
}