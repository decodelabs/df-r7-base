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


// Record loader
trait TScaffold_RecordLoader {

    //const RECORD_KEY_NAME = null;
    //const RECORD_ITEM_NAME = null;
    //const CLUSTER = false;
    //const GLOBAL_CLUSTER = false;
    //const CLUSTER_KEY = null;

    protected $_recordAdapter;
    protected $_clusterRecord;
    protected $_clusterId;
    protected $_clusterKey;

    public function getRecordAdapter() {
        if($this->_recordAdapter) {
            return $this->_recordAdapter;
        }

        if(@static::RECORD_ADAPTER) {
            $locator = static::RECORD_ADAPTER;

            if($this->isRecordAdapterClustered()) {
                $locator = new mesh\entity\Locator($locator);
                $clusterId = $this->getClusterId();
                $location = $clusterId.'/'.$locator->getLastNodeLocation();
                $locator->setLastNodeLocation($location);
            }

            $adapter = $this->data->fetchEntity($locator);

            if($adapter instanceof axis\IUnit) {
                $this->_recordAdapter = $adapter;
                return $adapter;
            }
        } else if($this->_recordAdapter = $this->_generateRecordAdapter()) {
            return $this->_recordAdapter;
        }

        throw new LogicException(
            'Unable to find a suitable adapter for record scaffold'
        );
    }

    protected function _generateRecordAdapter() {}

    public function isRecordAdapterClustered() {
        return (bool)@static::CLUSTER;
    }

    public function getClusterKey() {
        if(@static::CLUSTER_KEY !== null) {
            return static::CLUSTER_KEY;
        }

        if(!$this->_clusterKey) {
            $unit = $this->data->getClusterUnit();
            $this->_clusterKey = $unit->getUnitName();
        }

        return $this->_clusterKey;
    }

    public function getClusterRecord() {
        if($this->_clusterRecord === null) {
            $id = $this->request->query[$this->getClusterKey()];

            if(empty($id)) {
                if(@static::GLOBAL_CLUSTER) {
                    $this->_clusterRecord = false;
                } else {
                    throw new RuntimeException(
                        'Unable to extract cluster id from request with key: '.$this->getClusterKey()
                    );
                }
            }

            if($this->_clusterRecord !== false) {
                $this->_clusterRecord = $this->data->fetchClusterRecord($id);
            }
        }

        if($this->_clusterRecord === false) {
            return null;
        }

        return $this->_clusterRecord;
    }

    public function getClusterId() {
        if($this->_clusterId === null) {
            if($record = $this->getClusterRecord()) {
                $this->_clusterId = (string)$record->getPrimaryKeySet();
            } else {
                $this->_clusterId = false;
            }
        }

        if($this->_clusterId === false) {
            return null;
        }

        return $this->_clusterId;
    }

    protected function _renderClusterSelector() {
        if(!$this->isRecordAdapterClustered() || !($unit = $this->data->getClusterUnit())) {
            return;
        }

        if($unit instanceof axis\IClusterUnit) {
            $list = $unit->getClusterOptionsList();
        } else {
            $list = $unit->select('@primary')
                ->orderBy('@primary ASC')
                ->toList('@primary', '@primary');
        }

        $form = $this->html->form()->setMethod('get');
        $clusterName = $this->format->name($unit->getUnitName());
        $clusterKey = $this->getClusterKey();

        if(@static::GLOBAL_CLUSTER) {
            $selector = $this->html->groupedSelectList($clusterKey, $this->request->query->{$clusterKey}, [
                    'Global' => [
                        '' => 'Global'
                    ],
                    $clusterName => $list
                ])
                ->isRequired(true);
        } else {
            $selector = $this->html->selectList($clusterKey, $this->request->query->{$clusterKey}, $list)
                ->isRequired(true);
        }

        $form->addFieldArea($clusterName)->push(
            $selector,

            $this->html->submitButton(null, $this->_('Go'))
                ->setDisposition('positive')
        );

        return $form;
    }

    protected function _handleClusterSelection() {
        if(!$this->isRecordAdapterClustered() || @static::GLOBAL_CLUSTER || !($unit = $this->data->getClusterUnit())) {
            return null;
        }

        $clusterKey = $this->getClusterKey();
        $clusterId = $this->request->query[$clusterKey];

        if(!empty($clusterId)) {
            return null;
        }

        if($unit instanceof axis\IClusterUnit) {
            $list = $unit->getClusterOptionsList();
        } else {
            $list = $unit->select('@primary')
                ->orderBy('@primary ASC')
                ->toList('@primary', '@primary');
        }

        if(count($list) == 1) {
            $request = clone $this->request;
            $request->query->{$clusterKey} = key($list);
            return $this->http->redirect($request);
        }

        //$this->view = $this->apex->newWidgetView();
        $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));
        $container = $this->view->content;
        $clusterName = $this->format->name($unit->getUnitName());

        $container->push(
            (new arch\scaffold\component\HeaderBar($this, 'clusterSelector', []))
                ->setTitle($this->getDirectoryTitle())
        );

        if(empty($list)) {
            $container->addFlashMessage($this->_(
                'There are currently no %n% group entries to select from',
                ['%n%' => $clusterName]
            ), 'warning');

            return $this->view;
        }

        $form = $container->addForm()->setMethod('get');
        $fs = $form->addFieldSet($this->_('Select %n%', ['%n%' => $clusterName]));

        $fs->addFieldArea()->addFlashMessage($this->_(
            'This data is split over multiple %n% groups, please select which you would like to view',
            ['%n%' => $clusterName]
        ));

        $fs->addFieldArea($clusterName)->push(
            $this->html->selectList($clusterKey, null, $list)
                ->isRequired(true),

            $this->_buildQueryPropagationInputs([$clusterKey]),

            $this->html->submitButton(null, $this->_('Select'))
                ->setDisposition('positive')
        );
        
        return $this->view;
    }

    public function getPropagatingQueryVars() {
        $output = parent::getPropagatingQueryVars();

        if($this->isRecordAdapterClustered()) {
            $output[] = $this->getClusterKey();
        }

        return $output;
    }

    public function getRecordKeyName() {
        if(@static::RECORD_KEY_NAME) {
            return static::RECORD_KEY_NAME;
        }

        $adapter = $this->getRecordAdapter();

        if($adapter instanceof axis\ISchemaDefinitionStorageUnit) {
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

        return strtolower(core\string\Manipulator::formatName($this->getRecordKeyName()));
    }
}

// Record provider
trait TScaffold_RecordDataProvider {

    //const RECORD_ID_FIELD = 'id';
    //const RECORD_NAME_FIELD = 'name';
    //const RECORD_URL_KEY = null;
    //const RECORD_ADAPTER = null;

    //const CAN_ADD_RECORD = true;
    //const CAN_EDIT_RECORD = true;
    //const CAN_DELETE_RECORD = true;

    protected $_record;
    protected $_recordAction;
    protected $_recordDetailsFields = [];

    private $_recordNameKey;

    public function getRecord() {
        $this->_ensureRecord();
        return $this->_record;
    }

    public function getRecordId($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }

        if($record instanceof opal\record\IPrimaryKeySetProvider) {
            return (string)$record->getPrimaryKeySet();
        }

        $idKey = $this->getRecordIdField();
        return @$record[$idKey];
    }

    public function getRecordName($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }

        $key = $this->getRecordNameField();

        if(method_exists($this, '_getRecordName')) {
            $output = $this->_getRecordName($record);
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

                $output = '#'.$this->getRecordId($record);

                if($available) {
                    switch($key) {
                        case 'title':
                            $output = [
                                $this->html('em', $this->_('untitled %c%', ['%c%' => $this->getRecordItemName()])),
                                $this->html('samp', $output)
                            ];
                            break;

                        case 'name':
                            $output = [
                                $this->html('em', $this->_('unnamed %c%', ['%c%' => $this->getRecordItemName()])),
                                $this->html('samp', $output)
                            ];
                            break;
                    }
                } else {
                    $output = [
                        $this->html('em', $this->getRecordItemName()),
                        $this->html('samp', $output)
                    ];
                }
            }
        }

        return $this->_normalizeFieldOutput($key, $output);
    }

    public function getRecordDescription($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }
        
        return $this->html->toText($this->_describeRecord($record));
    }

    protected function _describeRecord($record) {
        return $this->getRecordName($record);
    }

    public function getRecordUrl($record=null) {
        if(!$record) {
            $record = $this->_ensureRecord();
        }

        return $this->_getRecordActionRequest($record, 'details');
    }

    protected function _ensureRecord() {
        if($this->_record) {
            return $this->_record;
        }

        $key = $this->context->request->query[$this->_getRecordUrlKey()];
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

                if($adapter instanceof axis\ISchemaDefinitionStorageUnit) {
                    $this->_recordNameKey = $adapter->getRecordNameField();
                } else {
                    $this->_recordNameKey = 'name';
                }
            }
        }

        return $this->_recordNameKey;
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


    protected function _getRecordActionRequest($record, $action, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]) {
        return $this->_getActionRequest($action, [
            $this->_getRecordUrlKey() => $this->getRecordId($record)
        ], $redirFrom, $redirTo, $propagationFilter);
    }



    public function buildDeleteDynamicAction($controller) {
        if(!$this->canDeleteRecord()) {
            $this->context->throwError(403, 'Records cannot be deleted');
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
        $output = new arch\component\template\AttributeList($this->context, $args);
        $output->setViewArg(lcfirst($this->getRecordKeyName()));

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method1 = 'define'.ucfirst($field).'Field';
                $method2 = 'override'.ucfirst($field).'Field';

                if(method_exists($this, $method2)) {
                    $output->setField($field, function($list, $key) use($method2, $field) {
                        if(false === $this->{$method2}($list, 'details')) {
                            $list->addField($key);
                        }
                    });
                } else if(method_exists($this, $method1)) {
                    $output->setField($field, function($list, $key) use($method1, $field) {
                        if(false === $this->{$method1}($list, 'details')) {
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
                            $this->context->location->getPath()->getDirname().'/' : null
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
                    ->setMaxLength(50);
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

    public function defineUserField($list, $mode) {
        $list->addField('user', function($item) {
            return $this->apex->component('~admin/users/clients/UserLink', $item['user'])
                ->setDisposition('transitive');
        });
    }

    public function defineOwnerField($list, $mode) {
        $list->addField('owner', function($item) {
            return $this->apex->component('~admin/users/clients/UserLink', $item['owner'])
                ->setDisposition('transitive');
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
        $query->searchFor($search);
    }

    protected function _prepareRecordListQuery(opal\query\ISelectQuery $query, $mode) {}

    public function buildListComponent(array $args) {
        if(!isset($args[0])) {
            $args[0] = [];
        }

        $args[0] = array_merge($this->_recordListFields, $args[0]);
        $output = new arch\component\template\CollectionList($this->context, $args);
        $output->setViewArg(lcfirst($this->getRecordKeyName()).'List');
        $nameKey = $this->getRecordNameField();

        foreach($output->getFields() as $field => $enabled) {
            if($enabled === true) {
                $method1 = 'define'.ucfirst($field).'Field';
                $method2 = 'override'.ucfirst($field).'Field';

                if(method_exists($this, $method2)) {
                    $output->setField($field, function($list, $key) use($method2, $field, $nameKey) {
                        if(false === $this->{$method2}($list, 'list')) {
                            $list->addField($key);
                        }
                    });
                } else if(method_exists($this, $method1)) {
                    $output->setField($field, function($list, $key) use($method1, $field, $nameKey) {
                        if($field == $nameKey) {
                            return $this->_autoDefineNameKeyField($field, $list, 'list');
                        } else {
                            if(false === $this->{$method1}($list, 'list')) {
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
        return new arch\scaffold\delegate\SearchSelector($this, $state, $id);
    }
}






trait TScaffold_SectionProvider {

    protected $_sections = null;
    private $_sectionItemCounts = null;

    protected function _getSections() {
        if($this->_sections === null) {
            $this->_sections = $this->_generateSections();
        }

        return $this->_sections;
    }

    protected function _generateSections() {
        return ['details'];
    }

    public function loadSectionAction(arch\IController $controller=null) {
        $action = $this->context->request->getAction();
        $sections = $this->_getSections();

        if(isset($sections[$action]) || in_array($action, $sections)) {
            return new Action($this->context, $this, function() use($action) {
                $record = null;

                if($this instanceof IRecordDataProviderScaffold) {
                    $record = $this->getRecord();
                }

                //$this->view = $this->apex->newWidgetView();
                $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

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
            }, $controller);
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
            ->setBackLinkRequest($this->_getSectionHeaderBarBackLinkRequest());
    }

    protected function _getSectionHeaderBarBackLinkRequest() {
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

        foreach($sections as $action => $set) {
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