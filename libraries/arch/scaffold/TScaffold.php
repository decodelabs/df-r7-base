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

    //const CAN_ADD_RECORD = true;
    //const CAN_EDIT_RECORD = true;
    //const CAN_DELETE_RECORD = true;

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

        $key = $this->getRecordNameKey();
        return $this->_normalizeFieldOutput($key, $record[$key]);
    }

    public function getRecordDescription($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }
        
        return $this->_describeRecord($record);
    }

    protected function _describeRecord($record) {
        return $this->getRecordName($record);
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


    public function canAddRecord() {
        return static::CAN_ADD_RECORD;
    }

    public function canEditRecord($record=null) {
        return static::CAN_EDIT_RECORD;
    }

    public function canDeleteRecord($record=null) {
        return static::CAN_DELETE_RECORD;
    }


    protected function _getRecordActionRequest($record, $action, array $query=null, $redirFrom=null, $redirTo=null) {
        return $this->_getActionRequest($action, [
            $this->_getRecordUrlKey() => $this->getRecordId($record)
        ], $redirFrom, $redirTo);
    }



    public function buildDeleteDynamicAction($controller) {
        if(!$this->canDeleteRecord()) {
            $this->_context->throwError(403, 'Records cannot be deleted');
        }

        $this->_recordAction = 'delete';
        return new arch\scaffold\form\Delete($this, $controller);
    }

    public function getRecordDeleteFlags() {
        return [];
    }

    public function deleteRecord(opal\record\IRecord $record, array $flags=[]) {
        $record->delete();
        return $this;
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
                $method = 'define'.ucfirst($field).'Field';

                if(method_exists($this, $method)) {
                    $output->setField($field, function($list, $key) use($method) {
                        if(false === $this->{$method}($list, 'details')) {
                            $list->addField($key);
                        }
                    });
                }
            }
        }

        return $output;
    }

    public function getRecordOperativeLinks($record, $mode) {
        $output = [];

        // Edit
        if($this->canEditRecord($record)) {
            $output[] = $this->html->link(
                    $this->_getRecordActionRequest($record, 'edit', null, true),
                    $this->_('Edit '.$this->getRecordItemName())
                )
                ->setIcon('edit');
        }

        // Delete
        if($this->canDeleteRecord($record)) {
            $output[] = $this->html->link(
                    $this->_getRecordActionRequest(
                        $record, 'delete', null, true,
                        $mode == 'sectionHeaderBar' ?
                            $this->_context->location->getPath()->getDirname().'/' : null
                    ),
                    $this->_('Delete '.$this->getRecordItemName())
                )
                ->setIcon('delete');
        }

        return $output;
    }


    public function defineWeightField($list, $mode) {
        $list->addField('weight', $mode == 'list' ? '#' : $this->_('Order number'));
    }

    public function defineUrlField($list, $mode) {
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

    public function defineUserField($list, $mode) {
        $list->addField('user', function($item) {
            return $this->import->component('UserLink', '~admin/users/clients/', $item['user'])
                ->setDisposition('transitive');
        });
    }

    public function defineEmailField($list, $mode) {
        $list->addField('email', function($item) {
            return $this->html->mailLink($item['email']);
        });
    }

    public function defineCreationDateField($list, $mode) {
        $list->addField('creationDate', $this->_('Created'), function($item) {
            return $this->html->timeSince($item['creationDate']);
        });
    }

    public function defineLastEditDateField($list) {
        $list->addField('lastEditDate', $this->_('Edited'), function($item) {
            return $this->html->timeSince($item['lastEditDate']);
        });
    }

    public function defineActionsField($list, $mode) {
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

    public function applyRecordQuerySearch(opal\query\ISelectQuery $query, $search, $mode) {
        $query->where($this->getRecordNameKey(), 'matches', $search);
    }

    protected function _prepareRecordListQuery(opal\query\ISelectQuery $query, $mode) {}

    public function buildListComponent(array $args) {
        if(!isset($args[0])) {
            $args[0] = [];
        }

        $args[0] = array_merge($this->_recordListFields, $args[0]);
        $output = new arch\component\template\CollectionList($this->_context, $args);
        $nameKey = $this->getRecordNameKey();

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method = 'define'.ucfirst($field).'Field';

                if(method_exists($this, $method)) {
                    $output->setField($field, function($list, $key) use($method, $field, $nameKey) {
                        if(false === $this->{$method}($list, 'list')) {
                            if($field == $nameKey) {
                                return $this->_autoDefineNameKeyField($field, $list, 'list');
                            } else {
                                $list->addField($key);
                            }
                        }
                    });
                } else if($field == $nameKey) {
                    $output->setField($field, function($list, $key) use($field) {
                        return $this->_autoDefineNameKeyField($field, $list, 'list');
                    });
                }
            }
        }

        return $output;
    }

    protected function _autoDefineNameKeyField($fieldName, $list, $mode) {
        $list->addField($fieldName, function($item) use($mode) {
            if($mode == 'list') {
                return $this->import->component(
                        ucfirst($this->getRecordKeyName().'Link'), 
                        $this->_context->location, 
                        $item
                    )
                    ->setMaxLength(50);
            }

            return $this->getRecordName($item);
        });
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

    public function buildSection($name, Callable $builder, Callable $linkBuilder=null) {
        $container = $this->aura->getWidgetContainer();
        $this->view = $container->getView();

        $args = [];
        $record = null;

        if($this instanceof IRecordDataProviderScaffold) {
            $args[] = $record = $this->getRecord();
        }

        $args[] = $this->view;
        $args[] = $this;

        $hb = $this->import->component('SectionHeaderBar', $this->_context->location, $record);

        if($hb instanceof arch\scaffold\component\HeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = call_user_func_array($builder, $args);
        $container->push($hb, $body);

        return $this->view;
    }

    public function buildSectionHeaderBarComponent(array $args) {
        return (new arch\scaffold\component\HeaderBar($this, 'section', $args))
            ->setTitle(
                $this instanceof IRecordDataProviderScaffold ?
                    $this->_(
                        ucfirst($this->getRecordItemName()).': %n%',
                        ['%n%' => $this->format->shorten($this->getRecordDescription(), 50)]
                    ) :
                    $this->getDirectoryTitle()
            );
    }

    public function addSectionOperativeLinks($menu, $bar) {
        if($this instanceof IRecordDataProviderScaffold) {
            $menu->addLinks($this->getRecordOperativeLinks($this->getRecord(), 'sectionHeaderBar'));
        }
    }

    public function addSectionSubOperativeLinks($menu, $bar) {
        $action = $this->_context->request->getAction();
        $method = 'add'.ucfirst($action).'SectionSubOperativeLinks';

        if(method_exists($this, $method)) {
            $this->{$method}($menu, $bar);
        }
    }

    public function addSectionSectionLinks($menu, $bar) {
        $menu->addLinks($this->location->getPath()->getDirname().'Sections');
    }

    public function generateSectionsMenu($entryList) {
        if(count($this->_sections) == 1) {
            return;
        }

        $counts = $this->getSectionItemCounts();
        $i = 0;

        foreach($this->_sections as $action => $set) {
            $i++;

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

            $link = $entryList->newLink($request, $name)
                ->setId($action)
                ->setIcon($icon)
                ->setWeight($action == 'details' ? 1 : $i * 10)
                ->setDisposition('informative');

            if(isset($counts[$action])) {
                $link->setNote($this->format->counterNote($counts[$action]));
            }

            $entryList->addEntry($link);
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

    public function addIndexOperativeLinks($menu, $bar) {
        if($this->canAddRecord()) {
            $menu->addLinks(
                $bar->html->link(
                        $bar->uri->request($this->_getActionRequest('add'), true),
                        $this->_('Add '.$this->getRecordItemName())
                    )
                    ->setIcon('add')
                    ->addAccessLock($this->getRecordAdapter()->getEntityLocator()->toString().'#add')
            );
        }
    }
}