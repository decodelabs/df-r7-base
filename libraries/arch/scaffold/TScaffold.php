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
use df\mesh;
use df\flex;
use df\user;


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
        } else if($this->_recordAdapter = $this->generateRecordAdapter()) {
            return $this->_recordAdapter;
        }

        throw new LogicException(
            'Unable to find a suitable adapter for record scaffold'
        );
    }

    protected function generateRecordAdapter() {}

    public function getRecordKeyName() {
        if(@static::RECORD_KEY_NAME) {
            return static::RECORD_KEY_NAME;
        }

        $adapter = $this->getRecordAdapter();

        if($adapter instanceof axis\ISchemaBasedStorageUnit) {
            return $adapter->getRecordKeyName();
        } else if($adapter instanceof axis\IUnit) {
            return lcfirst($adapter->getUnitName());
        } else {
            return 'record';
        }
    }

    public function getRecordItemName() {
        if(@static::RECORD_ITEM_NAME) {
            return static::RECORD_ITEM_NAME;
        }

        return strtolower(flex\Text::formatName($this->getRecordKeyName()));
    }
}

// Record provider
trait TScaffold_RecordDataProvider {

    //const RECORD_ID_FIELD = 'id';
    //const RECORD_NAME_FIELD = 'name';
    //const RECORD_URL_KEY = null;
    //const RECORD_ADAPTER = null;
    //const DEFAULT_RECORD_ACTION = 'details';

    //const CAN_ADD_RECORD = true;
    //const CAN_EDIT_RECORD = true;
    //const CAN_DELETE_RECORD = true;

    protected $_record;
    protected $_recordAction;
    protected $_recordDetailsFields = [];

    private $_recordNameKey;

    public function newRecord(array $values=null) {
        return $this->data->newRecord($this->getRecordAdapter(), $values);
    }

    public function getRecord() {
        if($this->_record) {
            return $this->_record;
        }

        $key = $this->context->request->query[$this->getRecordUrlKey()];
        $this->_record = $this->loadRecord($key, $this->_recordAction);

        if(!$this->_record) {
            throw new RuntimeException('Unable to load scaffold record');
        }

        return $this->_record;
    }

    public function getRecordId($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }

        if($record instanceof opal\record\IPrimaryKeySetProvider) {
            return (string)$record->getPrimaryKeySet();
        }

        $idKey = $this->getRecordIdField();
        return @$record[$idKey];
    }

    public function getRecordName($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }

        $key = $this->getRecordNameField();

        if(method_exists($this, 'nameRecord')) {
            $output = $this->nameRecord($record);
        } else {
            if(isset($record[$key])) {
                $output = $record[$key];

                if($key == $this->getRecordIdField() && is_numeric($output)) {
                    $output = '#'.$output;
                }
            } else {
                if(is_array($record)) {
                    $available = array_key_exists($key, $record);
                } else if($record instanceof core\collection\IMappedCollection) {
                    $available = $record->has($key);
                } else {
                    $available = true;
                }

                $id = $this->getRecordId($record);

                if($available) {
                    switch($key) {
                        case 'title':
                            $output = $this->html('em', $this->_('untitled %c%', ['%c%' => $this->getRecordItemName()]));
                            break;

                        case 'name':
                            $output = $this->html('em', $this->_('unnamed %c%', ['%c%' => $this->getRecordItemName()]));
                            break;
                    }
                } else {
                    $output = $this->html('em', $this->getRecordItemName());
                }

                if(is_numeric($id)) {
                    $output = [$output, $this->html('samp', '#'.$id)];
                }
            }
        }

        return $this->_normalizeFieldOutput($key, $output);
    }

    public function getRecordDescription($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }

        return $this->html->toText($this->describeRecord($record));
    }

    protected function describeRecord($record) {
        return $this->getRecordName($record);
    }

    public function getRecordUrl($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }

        return $this->_getRecordActionRequest($record, static::DEFAULT_RECORD_ACTION);
    }

    public function getRecordIcon($record=null) {
        if(!$record) {
            try {
                $record = $this->getRecord();
            } catch(\Exception $e) {
                return $this->getDirectoryIcon();
            }
        }

        if(method_exists($this, 'iconifyRecord')) {
            return $this->iconifyRecord($record);
        } else {
            return $this->getDirectoryIcon();
        }
    }

    protected function loadRecord($key, $action) {
        return $this->data->fetchForAction(
            $this->getRecordAdapter(), $key, $action
        );
    }

    public function getRecordIdField() {
        if(@static::RECORD_ID_FIELD) {
            return static::RECORD_ID_FIELD;
        }

        return 'id';
    }

    public function getRecordNameField() {
        if($this->_recordNameKey === null) {
            if(@static::RECORD_NAME_FIELD) {
                $this->_recordNameKey = static::RECORD_NAME_FIELD;
            } else {
                $adapter = $this->getRecordAdapter();

                if($adapter instanceof axis\ISchemaBasedStorageUnit) {
                    $this->_recordNameKey = $adapter->getRecordNameField();
                } else {
                    $this->_recordNameKey = 'name';
                }
            }
        }

        return $this->_recordNameKey;
    }

    public function getRecordUrlKey() {
        if(@static::RECORD_URL_KEY) {
            return static::RECORD_URL_KEY;
        }

        return $this->getRecordKeyName();
    }


    protected function _countRecordRelations($record, $fields) {
        $fields = core\collection\Util::flattenArray(func_get_args());

        if($fields[0] instanceof opal\record\IRecord) {
            $record = array_shift($fields);
        } else  {
            $record = $this->getRecord();
        }

        $query = $this->getRecordAdapter()->select('@primary');

        foreach($fields as $field) {
            try {
                $query->countRelation($field);
            } catch(opal\query\InvalidArgumentException $e) {}
        }

        $output = [];
        $data = $query->where('@primary', '=', $record->getPrimaryKeySet())
            ->toRow();

        foreach($fields as $key => $field) {
            if(!isset($data[$field])) {
                continue;
            }

            if(!is_string($key)) {
                $key = $field;
            }

            $output[$key] = $data[$field];
        }

        return $output;
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


    protected function _getRecordActionRequest($record, $action, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]) {
        return $this->_getActionRequest($action, [
            $this->getRecordUrlKey() => $this->getRecordId($record)
        ], $redirFrom, $redirTo, $propagationFilter);
    }



    public function buildDeleteDynamicAction() {
        if(!$this->canDeleteRecord()) {
            $this->context->throwError(403, 'Records cannot be deleted');
        }

        $this->_recordAction = 'delete';
        return new arch\scaffold\form\Delete($this);
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
        $fields = array_shift($args);

        if(!is_array($fields)) {
            $fields = [];
        }

        $fields = array_merge($this->_recordDetailsFields, $fields);
        $record = array_shift($args);

        return $this->generateAttributeList($fields, $record);
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
                            $this->context->location->getPath()->getDirname() : null
                    ),
                    $this->_('Delete '.$this->getRecordItemName())
                )
                ->setIcon('delete');
        }

        return $output;
    }

    protected function _autoDefineNameKeyField($fieldName, $list, $mode, $label=null) {
        if($label === null) {
            $label = $this->format->name($fieldName);
        }

        $list->addField($fieldName, $label, function($item) use($mode, $fieldName) {
            if($mode == 'list') {
                return $this->apex->component(
                        ucfirst($this->getRecordKeyName().'Link'),
                        $item
                    )
                    ->setMaxLength(50)
                    ->setDisposition('informative');
            }

            $output = $this->getRecordName($item);

            if($fieldName == 'slug') {
                $output = $this->html('samp', $output);
            }

            return $output;
        });
    }

    public function defineSlugField($list, $mode) {
        $list->addField('slug', function($item) {
            return $this->html('samp', $item['slug']);
        });
    }

    public function defineWeightField($list, $mode) {
        $list->addField('weight', $mode == 'list' ? '#' : $this->_('Order number'));
    }

    public function defineUrlField($list, $mode) {
        $list->addField('url', function($item) use($mode) {
            $url = $item['url'];

            if($url === null) {
                return $url;
            }

            if($mode == 'list') {
                $url = $this->uri->__invoke($url);
                $name = $url->getDomain();
            } else {
                $name = $url;
            }

            return $this->html->link($url, $name)
                ->setIcon('link')
                ->setTarget('_blank');
        });
    }

    public function defineWebsiteField($list, $mode) {
        $list->addField('website', function($item) use($mode) {
            $url = $item['website'];

            if($url === null) {
                return $url;
            }

            if($mode == 'list') {
                $url = $this->uri->__invoke($url);
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
            return $this->apex->component('~admin/users/clients/UserLink', $item['user']);
        });
    }

    public function defineOwnerField($list, $mode) {
        $list->addField('owner', function($item) {
            return $this->apex->component('~admin/users/clients/UserLink', $item['owner']);
        });
    }

    public function defineEmailField($list, $mode) {
        $list->addField('email', function($item) {
            return $this->html->mailLink($item['email']);
        });
    }

    public function defineCreationDateField($list, $mode) {
        $list->addField('creationDate', $this->_('Created'), function($item) use($mode) {
            if($mode == 'list') {
                return $this->html->timeSince($item['creationDate']);
            } else {
                return $this->html->timeFromNow($item['creationDate']);
            }
        });
    }

    public function defineLastEditDateField($list, $mode) {
        $list->addField('lastEditDate', $this->_('Edited'), function($item) use($mode) {
            if($mode == 'list') {
                return $this->html->timeSince($item['lastEditDate']);
            } else {
                return $this->html->timeFromNow($item['lastEditDate']);
            }
        });
    }

    public function defineIsLiveField($list, $mode) {
        $list->addField('isLive', $this->_('Live'), function($item, $context) {
            if(!$item['isLive']) {
                $context->getRowTag()->addClass('disabled');
            }

            return $this->html->booleanIcon($item['isLive']);
        });
    }

    public function definePriorityField($list, $mode) {
        $list->addField('priority', function($item) {
            $priority = core\unit\Priority::factory($item['priority']);
            return $this->html->icon('priority-'.$priority->getOption(), $priority->getLabel())
                ->addClass('priority-'.$priority->getOption());
        });
    }

    public function defineArchiveDateField($list, $mode) {
        $list->addField('archiveDate', $this->_('Archive'), function($item, $context) use($mode) {
            $date = $item['archiveDate'];
            $hasDate = (bool)$date;
            $isPast = $hasDate && $date->isPast();

            if($isPast && $mode == 'list') {
                $context->getRowTag()->addClass('inactive');
            }

            $output = $this->html->userDate($date);

            if($output) {
                if($isPast) {
                    $output->addClass('negative');
                } else if($date->lt('+1 month')) {
                    $output->addClass('warning');
                } else {
                    $output->addClass('positive');
                }
            }

            return $output;
        });
    }

    public function defineColorField($list, $mode) {
        $list->addField('color', function($item, $context) {
            $color = df\neon\Color::factory('black');
            return $this->html('span', $item['color'])
                ->setStyle('background', $item['color'])
                ->setStyle('color', $color->contrastAgainst($item['color'], 1))
                ->setStyle('padding', '0 0.6em');
        });
    }

    public function defineEnvironmentModeField($list, $mode) {
        $list->addField('environmentMode', $mode == 'list' ? $this->_('Env.') : null, function($mail) use($mode) {
            switch($mail['environmentMode']) {
                case 'development':
                    return $this->html('span.priority-low.inactive', $mode == 'list' ? $this->_('Dev') : $this->_('Development'));
                case 'testing':
                    return $this->html('span.priority-medium.inactive', $mode == 'list' ? $this->_('Test') : $this->_('Testing'));
                case 'production':
                    return $this->html('span.priority-high.active', $mode == 'list' ? $this->_('Prod') : $this->_('Production'));
            }
        });
    }

    public function defineAddressField($list, $mode) {
        if($mode == 'list') {
            $list->addField('address', function($item) {
                if(!$addr = $item['address']) {
                    return;
                }

                if(!$addr instanceof user\IPostalAddress) {
                    return $addr;
                }

                return $this->html(
                        'span',
                        $addr['city'].', '.$addr['country'],
                        ['title' => str_replace("\n", ', ', $addr->toString())]
                    );
            });
        } else {
            $list->addField('address', function($item) {
                return $this->html->address($item['address']);
            });
        }
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

    public function queryRecordList($mode, array $fields=null) {
        $output = $this->getRecordAdapter()->select($fields);
        $this->prepareRecordList($output, $mode);
        return $output;
    }

    public function extendRecordList(opal\query\ISelectQuery $query, $mode) {
        $this->prepareRecordList($query, $mode);
        return $query;
    }

    protected function prepareRecordList($query, $mode) {}

    public function applyRecordListSearch(opal\query\ISelectQuery $query, $search) {
        $this->searchRecordList($query, $search);
        return $query;
    }

    protected function searchRecordList($query, $search) {
        $query->searchFor($search);
    }


    public function buildListComponent(array $args) {
        $fields = array_shift($args);

        if(!is_array($fields)) {
            $fields = [];
        }

        $fields = array_merge($this->_recordListFields, $fields);
        $hasActions = false;

        foreach($fields as $key => $val) {
            if($key === 'actions' || $val === 'actions') {
                $hasActions = true;
                break;
            }
        }

        if(!$hasActions) {
            $fields['actions'] = true;
        }

        $collection = array_shift($args);
        return $this->generateCollectionList($fields, $collection);
    }

    public function generateSearchBarComponent() {
        $search = $this->request->getQueryTerm('search');
        $request = clone $this->context->request;
        $resetRequest = clone $request;
        $filter = ['search', 'lm', 'pg', 'of', 'od'];

        foreach($filter as $key) {
            $resetRequest->query->remove($key);
        }

        return $this->html->form($request)->setMethod('get')->push(
            $this->html->fieldSet($this->_('Search'))->push(
                $this->_buildQueryPropagationInputs($filter),

                $this->html->searchTextbox('search', $search),
                $this->html->submitButton(null, $this->_('Go'))
                    ->setIcon('search')
                    ->setDisposition('positive'),

                $this->html->link(
                        $resetRequest,
                        $this->_('Reset')
                    )
                    ->setIcon('refresh')
            )
        );
    }

    public function buildSelectorFormDelegate($state, $id) {
        return new arch\scaffold\delegate\Selector($this, $state, $id);
    }
}






trait TScaffold_SectionProvider {

    protected $_sections = null;
    private $_sectionsNormalized = false;
    private $_sectionItemCounts = null;

    protected function _getSections() {
        if($this->_sections === null) {
            $this->_sections = $this->generateSections();

            if(!is_array($this->_sections) || empty($this->_sections)) {
                $this->_sections = ['details'];
            }
        }

        if(!$this->_sectionsNormalized) {
            $sections = [];

            foreach($this->_sections as $key => $value) {
                if(is_int($key) && is_string($value)) {
                    $key = $value;
                    $value = null;
                }

                if(!is_array($value)) {
                    $value = ['icon' => $value];
                }

                if(!isset($value['icon'])) {
                    $value['icon'] = $key;
                }

                if(!isset($value['name'])) {
                    $value['name'] = $this->format->name($key);
                } else {
                    $value['name'] = $this->_($value['name']);
                }

                $sections[$key] = $value;
            }

            $this->_sections = $sections;
        }

        return $this->_sections;
    }

    protected function generateSections() {
        return ['details'];
    }

    public function loadSectionAction() {
        $action = $this->context->request->getAction();
        $sections = $this->_getSections();

        if(isset($sections[$action])) {
            return $this->_generateAction(function() use($action) {
                $record = null;

                if($this instanceof IRecordDataProviderScaffold) {
                    $record = $this->getRecord();
                }

                $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

                if(method_exists($this, '_prepareSection')) {
                    $this->_prepareSection($record, $action);
                }

                $method = 'render'.ucfirst($action).'SectionBody';

                if(method_exists($this, $method)) {
                    $body = $this->{$method}($record);
                } else {
                    $body = null;
                }

                $this->view->content->push(
                    $this->apex->component('SectionHeaderBar', $record),
                    $body
                );

                return $this->view;
            });
        }
    }

    public function buildSection($name, $builder, $linkBuilder=null) {
        //$this->view = $this->apex->newWidgetView();
        $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

        $args = [];
        $record = null;

        if($this instanceof IRecordDataProviderScaffold) {
            $args[] = $record = $this->getRecord();
        }

        $args[] = $this->view;
        $args[] = $this;

        $hb = $this->apex->component('SectionHeaderBar', $record);

        if($hb instanceof arch\scaffold\component\HeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = core\lang\CallbackArgs($builder, $args);
        $this->view->content->push($hb, $body);

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
            )
            ->setIcon($this->getRecordIcon())
            ->setBackLinkRequest($this->getParentSectionRequest());
    }

    protected function getParentSectionRequest() {
        return $this->_getActionRequest('index');
    }

    public function addSectionOperativeLinks($menu, $bar) {
        if($this instanceof IRecordDataProviderScaffold) {
            $menu->addLinks($this->getRecordOperativeLinks($this->getRecord(), 'sectionHeaderBar'));
        }
    }

    public function addSectionSubOperativeLinks($menu, $bar) {
        $action = $this->context->request->getAction();
        $method = 'add'.ucfirst($action).'SectionSubOperativeLinks';

        if(method_exists($this, $method)) {
            $this->{$method}($menu, $bar);
        }
    }

    public function addSectionSectionLinks($menu, $bar) {
        $menu->addLinks($this->location->getPath()->getDirname().'Sections');

        if(count($menu->getEntries()) == 1) {
            $menu->clearEntries();
        }
    }

    public function addSectionTransitiveLinks($menu, $bar) {
        $action = $this->context->request->getAction();
        $method = 'add'.ucfirst($action).'SectionTransitiveLinks';

        if(method_exists($this, $method)) {
            $this->{$method}($menu, $bar);
        }
    }

    public function generateSectionsMenu($entryList) {
        $counts = $this->getSectionItemCounts();
        $sections = $this->_getSections();
        $i = 0;
        $record = null;

        if($this instanceof IRecordDataProviderScaffold) {
            try {
                $record = $this->getRecord();
            } catch(\Exception $e) {}
        }

        foreach($sections as $action => $set) {
            $i++;

            if($record) {
                $request = $this->_getRecordActionRequest($record, $action);
            } else {
                $request = $this->_getActionRequest($action);
            }

            $link = $entryList->newLink($request, $set['name'])
                ->setId($action)
                ->setIcon($set['icon'])
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
            try {
                $this->_sectionItemCounts = (array)$this->countSectionItems($this->getRecord());
            } catch(\Exception $e) {
                return [];
            }
        }

        return $this->_sectionItemCounts;
    }

    protected function countSectionItems($record) {
        $sections = $this->_getSections();
        unset($sections['details']);
        return $this->_countRecordRelations($record, array_keys($sections));
    }
}





// Index header bar provider
trait TScaffold_IndexHeaderBarProvider {

    public function buildIndexHeaderBarComponent(array $args=null) {
        return (new arch\scaffold\component\HeaderBar($this, 'index', $args))
            ->setTitle($this->getDirectoryTitle());
    }

    public function buildIndexSection($name, $builder, $linkBuilder=null) {
        //$this->view = $this->apex->newWidgetView();
        $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

        $args = [$this->view, $this];
        $hb = $this->apex->component('IndexHeaderBar');

        if($hb instanceof arch\scaffold\component\HeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = core\lang\CallbackArgs($builder, $args);
        $this->view->content->push($hb, $body);

        return $this->view;
    }
}

trait TScaffold_RecordIndexHeaderBarProvider {

    public function addIndexOperativeLinks($menu, $bar) {
        if($this->canAddRecord()) {
            $recordAdapter = $this->getRecordAdapter();

            $menu->addLinks(
                $bar->html->link(
                        $bar->uri($this->_getActionRequest('add'), true),
                        $this->_('Add '.$this->getRecordItemName())
                    )
                    ->setIcon('add')
                    ->chainIf($recordAdapter instanceof axis\IUnit, function($link) use($recordAdapter) {
                        $link->addAccessLock($recordAdapter->getEntityLocator()->toString().'#add');
                    })
            );
        }
    }
}