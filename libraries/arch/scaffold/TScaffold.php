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

    //const KEY_NAME = null;
    //const ITEM_NAME = null;

    protected $_recordAdapter;

    public function getRecordAdapter() {
        if($this->_recordAdapter) {
            return $this->_recordAdapter;
        }

        if(@static::ADAPTER) {
            $adapter = $this->data->fetchEntity(static::ADAPTER);

            if($adapter instanceof axis\IUnit) {
                $this->_recordAdapter = $adapter;
                return $adapter;
            }
        } else if($this->_recordAdapter = $this->generateRecordAdapter()) {
            return $this->_recordAdapter;
        }

        throw core\Error::EDefinition(
            'Unable to find a suitable adapter for record scaffold'
        );
    }

    protected function generateRecordAdapter() {}

    public function getRecordKeyName() {
        if(@static::KEY_NAME) {
            return static::KEY_NAME;
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
        if(@static::ITEM_NAME) {
            return static::ITEM_NAME;
        }

        return strtolower(flex\Text::formatName($this->getRecordKeyName()));
    }
}

// Record provider
trait TScaffold_RecordDataProvider {

    //const ID_FIELD = 'id';
    //const NAME_FIELD = 'name';
    //const URL_KEY = null;
    //const ADAPTER = null;
    //const DEFAULT_SECTION = 'details';

    //const CAN_ADD = true;
    //const CAN_EDIT = true;
    //const CAN_DELETE = true;

    //const DETAILS_FIELDS = [];

    protected $_record;

    private $_recordNameKey;

    public function newRecord(array $values=null) {
        return $this->data->newRecord($this->getRecordAdapter(), $values);
    }

    public function getRecord() {
        if($this->_record) {
            return $this->_record;
        }

        $key = $this->context->request->query[$this->getRecordUrlKey()];
        $this->_record = $this->loadRecord($key);

        if(!$this->_record) {
            throw core\Error::{'arch/scaffold/EValue,arch/scaffold/ENotFound'}('Unable to load scaffold record');
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

        return $this->idRecord($record);
    }

    protected function idRecord($record) {
        $idKey = $this->getRecordIdField();
        return @$record[$idKey];
    }

    public function getRecordName($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }


        $key = $this->getRecordNameField();
        $output = $this->nameRecord($record);

        return $this->_normalizeFieldOutput($key, $output);
    }

    protected function nameRecord($record) {
        $key = $this->getRecordNameField();

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

        return $output;
    }

    public function getRecordDescription($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }

        return $this->describeRecord($record);
    }

    protected function describeRecord($record) {
        return $this->getRecordName($record);
    }

    public function getRecordUrl($record=null) {
        if(!$record) {
            $record = $this->getRecord();
        }

        return $this->_getRecordNodeRequest($record, static::DEFAULT_SECTION);
    }

    public function getRecordIcon($record=null) {
        if(!$record) {
            try {
                $record = $this->getRecord();
            } catch(\Throwable $e) {
                return $this->getDirectoryIcon();
            }
        }

        if(method_exists($this, 'iconifyRecord')) {
            return $this->iconifyRecord($record);
        } else {
            return $this->getDirectoryIcon();
        }
    }

    protected function loadRecord($key) {
        return $this->data->fetchForAction(
            $this->getRecordAdapter(), $key
        );
    }

    public function getRecordIdField() {
        if(@static::ID_FIELD) {
            return static::ID_FIELD;
        }

        return 'id';
    }

    public function getRecordNameField() {
        if($this->_recordNameKey === null) {
            if(@static::NAME_FIELD) {
                $this->_recordNameKey = static::NAME_FIELD;
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
        if(@static::URL_KEY) {
            return static::URL_KEY;
        }

        return $this->getRecordKeyName();
    }


    protected function _countRecordRelations(opal\record\IRecord $record, ...$fields) {
        $fields = core\collection\Util::flatten($fields);
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


    public function decorateRecordLink($link, $component) {
        return $link;
    }


    public function canAddRecord() {
        return static::CAN_ADD;
    }

    public function canEditRecord($record=null) {
        return static::CAN_EDIT;
    }

    public function canDeleteRecord($record=null) {
        return static::CAN_DELETE;
    }


    protected function _getRecordNodeRequest($record, $node, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]) {
        return $this->_getNodeRequest($node, [
            $this->getRecordUrlKey() => $this->getRecordId($record)
        ], $redirFrom, $redirTo, $propagationFilter);
    }


    public function getRecordBackLinkRequest() {
        return $this->uri->directoryRequest($this->getParentSectionRequest());
    }


    public function buildDeleteDynamicNode() {
        if(!$this->canDeleteRecord()) {
            throw core\Error::{'arch/scaffold/ELogic,EUnauthorized'}(
                'Records cannot be deleted'
            );
        }

        return new arch\scaffold\node\DeleteForm($this);
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

        if(defined('static::DETAILS_FIELDS') && is_array(static::DETAILS_FIELDS)) {
            $fields = array_merge(static::DETAILS_FIELDS, $fields);
        } else if(defined('static::LIST_FIELDS') && is_array(static::LIST_FIELDS)) {
            $fields = array_merge(static::LIST_FIELDS, $fields);
        }

        $record = array_shift($args);

        return $this->generateAttributeList($fields, $record);
    }


    public function getRecordOperativeLinks($record, $mode) {
        $output = [];

        // Edit
        if($this->canEditRecord($record)) {
            $output[] = $this->html->link(
                    $this->_getRecordNodeRequest($record, 'edit', null, true),
                    $this->_('Edit '.$this->getRecordItemName())
                )
                ->setIcon('edit');
        }

        // Delete
        if($this->canDeleteRecord($record)) {
            static $back;
            $redirTo = null;

            if($mode !== 'list') {
                if(!isset($back)) {
                    $back = isset($this->request[$this->getRecordUrlKey()]) ?
                        $this->getRecordBackLinkRequest() : null;
                }

                $redirTo = $back;
            }

            $output[] = $this->html->link(
                    $this->_getRecordNodeRequest(
                        $record, 'delete', null, true, $redirTo
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

            $output = $this->html->date($date);

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
            try {
                $color = df\neon\Color::factory($item['color']);
            } catch(\Throwable $e) {
                return $item['color'];
            }

            return $this->html('span', $item['color'])
                ->setStyle('background', $item['color'])
                ->setStyle('color', $color->getTextContrastColor())
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

    // const LIST_FIELDS = [];
    // const SEARCH_FIELDS = [];

    public function queryRecordList($mode, array $fields=null) {
        $output = $this->getRecordAdapter()->select($fields);

        //if($fields === null) {
            $this->prepareRecordList($output, $mode);
        //}

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
        if(defined('static::SEARCH_FIELDS')
        && is_array(static::SEARCH_FIELDS)
        && !empty(static::SEARCH_FIELDS)) {
            $fields = static::SEARCH_FIELDS;
        } else {
            $fields = null;
        }

        $query->searchFor($search, $fields);
    }


    public function buildListComponent(array $args) {
        $fields = array_shift($args);

        if(!is_array($fields)) {
            $fields = [];
        }

        if(defined('static::LIST_FIELDS') && is_array(static::LIST_FIELDS)) {
            $fields = array_merge(static::LIST_FIELDS, $fields);
        }

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

    public function buildSelectorFormDelegate($state, $event, $id) {
        return new arch\scaffold\node\form\SelectorDelegate($this, $state, $event, $id);
    }
}






trait TScaffold_SectionProvider {

    // const SECTIONS = [];

    private $_sections = null;
    private $_sectionItemCounts = null;

    protected function _getSections() {
        if($this->_sections === null) {
            if(defined('static::SECTIONS') && !empty(static::SECTIONS)) {
                $definition = static::SECTIONS;
            } else {
                $definition = $this->generateSections();
            }

            if(!is_array($definition) || empty($definition)) {
                $definition = ['details'];
            }

            $sections = [];

            foreach($definition as $key => $value) {
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

    public function loadSectionNode() {
        $node = $this->context->request->getNode();
        $sections = $this->_getSections();

        if(isset($sections[$node])) {
            return $this->_generateNode(function() use($node) {
                $record = null;

                if($this instanceof IRecordDataProviderScaffold) {
                    $record = $this->getRecord();
                }

                $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));
                $method = 'render'.ucfirst($node).'SectionBody';

                if(method_exists($this, $method)) {
                    $body = $this->{$method}($record);
                } else {
                    $body = null;
                }

                $this->view->content->push(
                    $this->apex->component('SectionHeaderBar', $record),
                    $body
                );

                $breadcrumbs = $this->apex->breadcrumbs();
                $this->updateSectionBreadcrumbs($breadcrumbs, $record, $node);

                return $this->view;
            });
        }
    }

    protected function updateSectionBreadcrumbs($breadcrumbs, $record, $node) {
        $breadcrumbs->getEntryByIndex(-2)->setUri($this->uri->directoryRequest($this->getParentSectionRequest()));
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

        $body = core\lang\Callback($builder, ...$args);
        $this->view->content->push($hb, $body);

        return $this->view;
    }

    public function buildSectionHeaderBarComponent(array $args) {
        return (new arch\scaffold\component\HeaderBar($this, 'section', $args))
            ->setTitle(
                $this instanceof IRecordDataProviderScaffold ?
                    [
                        ucfirst($this->getRecordItemName()).': ',
                        $this->getRecordDescription()
                    ] :
                    $this->getDirectoryTitle()
            )
            ->setIcon($this->getRecordIcon())
            ->setBackLinkRequest($this->getParentSectionRequest());
    }

    protected function getParentSectionRequest() {
        return $this->_getNodeRequest('index');
    }

    public function addSectionOperativeLinks($menu, $bar) {
        if($this instanceof IRecordDataProviderScaffold) {
            $menu->addLinks($this->getRecordOperativeLinks($this->getRecord(), 'sectionHeaderBar'));
        }
    }

    public function addSectionSubOperativeLinks($menu, $bar) {
        $node = $this->context->request->getNode();
        $method = 'add'.ucfirst($node).'SectionSubOperativeLinks';

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
        $node = $this->context->request->getNode();
        $method = 'add'.ucfirst($node).'SectionTransitiveLinks';

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
            } catch(\Throwable $e) {}
        }

        foreach($sections as $node => $set) {
            $i++;

            if($record) {
                $request = $this->_getRecordNodeRequest($record, $node);
            } else {
                $request = $this->_getNodeRequest($node);
            }

            $link = $entryList->newLink($request, $set['name'])
                ->setId($node)
                ->setIcon($set['icon'])
                ->setWeight($node == 'details' ? 1 : $i * 10)
                ->setDisposition('informative');

            if(isset($counts[$node])) {
                $link->setNote($this->format->counterNote($counts[$node]));
            }

            $entryList->addEntry($link);
        }
    }

    public function getSectionItemCounts() {
        if($this->_sectionItemCounts === null) {
            try {
                $this->_sectionItemCounts = (array)$this->countSectionItems($this->getRecord());
            } catch(\Throwable $e) {
                if($this->app->isDevelopment()) {
                    throw $e;
                }

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
            ->setTitle($this->getDirectoryTitle())
            ->setBackLinkRequest($this->getIndexBackLinkRequest());
    }

    protected function getIndexBackLinkRequest() {
        return $this->uri->backRequest();
    }

    public function buildIndexSection($name, $builder, $linkBuilder=null) {
        //$this->view = $this->apex->newWidgetView();
        $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

        $args = [$this->view, $this];
        $hb = $this->apex->component('IndexHeaderBar');

        if($hb instanceof arch\scaffold\component\HeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = core\lang\Callback($builder, ...$args);
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
                        $bar->uri($this->_getNodeRequest('add'), true),
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
