<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold\Record;

use ArrayAccess;
use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;

use DecodeLabs\Spectrum\Color;
use DecodeLabs\Tagged as Html;

use DecodeLabs\Tagged\Markup;

use df\arch\component\AttributeList as AttributeListComponent;
use df\arch\component\CollectionList as CollectionListComponent;

use df\arch\IComponent as Component;
use df\arch\node\Form as FormNode;
use df\arch\node\form\State as FormState;

use df\arch\node\IDelegate as Delegate;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;

use df\arch\scaffold\Component\RecordLink as ScaffoldRecordLinkComponent;
use df\arch\scaffold\Node\DeleteForm as ScaffoldDeleteForm;

use df\arch\scaffold\Node\DeleteSelectedForm as ScaffoldDeleteSelectedForm;

use df\arch\scaffold\Node\Form\SelectorDelegate as ScaffoldSelectorDelegate;
use df\aura\view\IDeferredRenderable as DeferredRenderableView;
use df\aura\view\IView as View;
use df\axis\IUnit as Unit;
use df\core\unit\Priority as PriorityUnit;

use df\user\IPostalAddress as PostalAddress;
use Throwable;

trait DecoratorTrait
{
    //const DETAILS_FIELDS = [];
    //const LIST_FIELDS = [];

    // Node handlers
    public function buildRecordListNode(?callable $filter = null, array $fields = null): View
    {
        return $this->buildNode(
            $this->renderRecordList($filter, $fields)
        );
    }

    public function buildDeleteDynamicNode(): FormNode
    {
        if (!$this->canDeleteRecords()) {
            throw Exceptional::{'df/arch/scaffold/Logic,Unauthorized'}(
                'Records cannot be deleted'
            );
        }

        return new ScaffoldDeleteForm($this);
    }

    public function buildDeleteSelectedDynamicNode(): FormNode
    {
        if (!$this->canDeleteRecords()) {
            throw Exceptional::{'df/arch/scaffold/Logic,Unauthorized'}(
                'Records cannot be deleted'
            );
        }

        return new ScaffoldDeleteSelectedForm($this);
    }


    // Section handlers
    public function renderDetailsSectionBody($record)
    {
        $keyName = $this->getRecordKeyName();
        return $this->apex->component(ucfirst($keyName) . 'Details', null, $record);
    }


    // Delegate handlers
    public function buildSelectorFormDelegate(FormState $state, FormEventDescriptor $event, string $id): Delegate
    {
        return new ScaffoldSelectorDelegate($this, $state, $event, $id);
    }




    // Component builders
    public function buildListComponent(array $args): Component
    {
        $fields = array_shift($args);

        if (!is_array($fields)) {
            $fields = [];
        }

        if (
            defined('static::LIST_FIELDS') &&
            is_array(static::LIST_FIELDS)
        ) {
            $fields = array_merge(static::LIST_FIELDS, $fields);
        }

        $hasActions = false;

        foreach ($fields as $key => $val) {
            if ($key === 'actions' || $val === 'actions') {
                $hasActions = true;
                break;
            }
        }

        if (!$hasActions) {
            $fields['actions'] = true;
        }

        $collection = array_shift($args);
        return $this->generateCollectionList($fields, $collection);
    }

    public function buildDetailsComponent(array $args): Component
    {
        $fields = array_shift($args);

        if (!is_array($fields)) {
            $fields = [];
        }

        if (
            defined('static::DETAILS_FIELDS') &&
            is_array(static::DETAILS_FIELDS)
        ) {
            $fields = array_merge(static::DETAILS_FIELDS, $fields);
        } elseif (
            defined('static::LIST_FIELDS') &&
            is_array(static::LIST_FIELDS)
        ) {
            $fields = array_merge(static::LIST_FIELDS, $fields);
        }

        $record = array_shift($args);
        return $this->generateAttributeList($fields, $record);
    }

    public function buildLinkComponent(array $args): Component
    {
        return new ScaffoldRecordLinkComponent($this, $args);
    }




    public function generateSearchBarComponent()
    {
        $search = $this->request->getQueryTerm('search');
        $request = clone $this->context->request;
        $resetRequest = clone $request;
        $filter = ['search', 'lm', 'pg', 'of', 'od'];

        foreach ($filter as $key) {
            $resetRequest->query->remove($key);
        }

        return $this->html->form($request)->setMethod('get')->push(
            $this->html->fieldSet()->addClass('scaffold search')->push(
                $this->buildQueryPropagationInputs($filter),
                $this->html->searchTextbox('search', $search)
                    ->setPlaceholder('search'),
                $this->html->submitButton(null, $this->_('Go'))
                    ->setIcon('search')
                    ->addClass('slim')
                    ->setDisposition('positive'),
                $this->html->link(
                    $resetRequest,
                    $this->_('Reset')
                )
                    ->setIcon('refresh')
            )
        );
    }

    public function generateSelectBarComponent()
    {
        return $this->html->fieldSet()->push(
            Html::{'div.label'}($this->_('With selected:')),
            function () {
                $menu = $this->html->menuBar();
                $this->addSelectBarLinks($menu);
                return $menu;
            }
        )->addClass('scaffold with-selected');
    }

    public function addSelectBarLinks($menu)
    {
        $menu->addLinks(
            $this->html->link(
                $this->uri('./delete-selected', true),
                $this->_('Delete')
            )
                ->setIcon('delete')
        );
    }



    public function generateIndexOperativeLinks(): iterable
    {
        if (!$this->canAddRecords()) {
            return;
        }

        $recordAdapter = $this->getRecordAdapter();

        yield 'add' => $this->html->link(
            $this->uri($this->getNodeUri('add'), true),
            $this->_('Add ' . $this->getRecordItemName())
        )
            ->setIcon('add')
            ->chainIf($recordAdapter instanceof Unit, function ($link) use ($recordAdapter) {
                $link->addAccessLock($recordAdapter->getEntityLocator()->toString() . '#add');
            });
    }



    // List generators
    public function generateCollectionList(array $fields, ?iterable $collection = null): CollectionListComponent
    {
        if (!$this instanceof DataProvider) {
            throw Exceptional::Logic(
                'Scaffold cannot generate collection list'
            );
        }

        $nameKey = $this->getRecordNameField();

        if (empty($fields) || (count($fields) == 1 && current($fields) == 'actions')) {
            array_unshift($fields, $nameKey);
        }

        $output = new CollectionListComponent($this->context, [$fields, $collection]);
        $output->setViewArg(lcfirst($this->getRecordKeyName()) . 'List');
        $output->setRenderTarget($this->view);

        foreach ($output->getFields() as $field => $enabled) {
            if ($enabled === true) {
                $method1 = 'define' . ucfirst($field) . 'Field';
                $method2 = 'override' . ucfirst($field) . 'Field';

                if (method_exists($this, $method2)) {
                    $output->setField($field, function ($list, $key) use ($method2) {
                        if (false === $this->{$method2}($list, 'list')) {
                            $list->addField($key);
                        }
                    });
                } elseif (method_exists($this, $method1)) {
                    $output->setField($field, function ($list, $key) use ($method1, $field, $nameKey) {
                        if ($field == $nameKey) {
                            return $this->autoDefineNameKeyField($field, $list, 'list');
                        } else {
                            if (false === $this->{$method1}($list, 'list')) {
                                $list->addField($key);
                            }
                        }
                    });
                } elseif ($field == $nameKey) {
                    $output->setField($field, function ($list, $key) use ($field) {
                        return $this->autoDefineNameKeyField($field, $list, 'list');
                    });
                }
            }
        }

        return $output;
    }

    public function generateAttributeList(array $fields, $record = true): AttributeListComponent
    {
        if (!$this instanceof DataProvider) {
            throw Exceptional::Logic(
                'Scaffold cannot generate attribute list'
            );
        }

        if ($record === true) {
            $record = $this->getRecord();
        }

        $output = new AttributeListComponent($this->context, [$fields, $record]);
        $output->setViewArg(lcfirst($this->getRecordKeyName()));
        $output->setRenderTarget($this->view);
        $spacerIterator = 0;

        foreach ($output->getFields() as $field => $enabled) {
            /*
            if(substr($field, 0, 2) == '--') {
                $output->setField('divider'.($spacerIterator++), function($list, $key) use($field) {
                    $list->addField($key, function($data, $context) use($field) {
                        if($field == '--') {
                            $context->addDivider();
                        } else {
                            $context->setDivider(ucfirst(substr($field, 2)));
                        }

                        $context->skipRow();
                    });
                });

                continue;
            }
             */

            if ($enabled === true) {
                $method1 = 'define' . ucfirst($field) . 'Field';
                $method2 = 'override' . ucfirst($field) . 'Field';
                $method = null;

                if (method_exists($this, $method2)) {
                    $method = $method2;
                } elseif (method_exists($this, $method1)) {
                    $method = $method1;
                }

                $output->setField($field, function ($list, $key) use ($method) {
                    if ($method) {
                        $ops = $this->{$method}($list, 'details');
                    } else {
                        $ops = false;
                    }

                    if ($ops === false) {
                        $list->addField($key, function ($data, $renderContext) {
                            $key = $renderContext->getField();
                            $value = null;

                            if (is_array($data)) {
                                if (isset($data[$key])) {
                                    $value = $data[$key];
                                } else {
                                    $value = null;
                                }
                            } elseif ($data instanceof ArrayAccess) {
                                $value = $data[$key];
                            } elseif (is_object($data)) {
                                if (method_exists($data, '__get')) {
                                    $value = $data->__get($key);
                                } elseif (method_exists($data, 'get' . ucfirst($key))) {
                                    $value = $data->{'get' . ucfirst($key)}();
                                }
                            }

                            if (
                                $value instanceof DeferredRenderableView &&
                                $this->view
                            ) {
                                $value->setRenderTarget($this->view);
                            }

                            return $value;
                        });
                    }
                });
            }
        }

        return $output;
    }




    // Link set generators
    public function decorateRecordLink($link, $component)
    {
        return $link;
    }



    public function generateRecordOperativeLinks(array $record): iterable
    {
        // Preview
        yield 'preview' => $this->generateRecordPreviewLink($record);

        // Edit
        yield 'edit' => $this->generateRecordEditLink($record);

        // Delete
        yield 'delete' => $this->generateRecordDeleteLink($record);
    }

    protected function generateRecordPreviewLink(array $record): ?Markup
    {
        if (!$this->canPreviewRecords()) {
            return null;
        }

        $url = $this->getRecordPreviewUri($record);

        return $this->html->link($url, $this->_('Preview'))
            ->setTarget('_blank')
            ->setIcon('preview')
            ->setDisposition('transitive')
            ->isDisabled($url === null);
    }

    protected function generateRecordEditLink(array $record): ?Markup
    {
        if (!$this->canEditRecords()) {
            return null;
        }

        return $this->html->link(
            $this->getRecordUri($record, 'edit', null, true),
            $this->_('Edit %n%', [
                '%n%' => $this->getRecordItemName()
            ])
        )
            ->setIcon('edit')
            ->isDisabled(!$this->isRecordEditable($record));
    }

    protected function generateRecordDeleteLink(array $record): ?Markup
    {
        if (!$this->canDeleteRecords()) {
            return null;
        }

        $redirTo = isset($this->request[$this->getRecordUrlKey()]) ?
            $this->getRecordParentUri($record) : null;

        return $this->html->link(
            $this->getRecordUri($record, 'delete', null, true, $redirTo),
            $this->_('Delete %n%', [
                '%n%' => $this->getRecordItemName()
            ])
        )
            ->setIcon('delete')
            ->isDisabled(!$this->isRecordDeleteable($record));
    }




    // Fields
    public function autoDefineNameKeyField(string $fieldName, $list, string $mode, ?string $label = null)
    {
        if ($label === null) {
            $label = Dictum::name($fieldName);
        }

        $list->addField($fieldName, $label, function ($item) use ($mode, $fieldName) {
            if ($mode == 'list') {
                return $this->apex->component(
                    ucfirst($this->getRecordKeyName() . 'Link'),
                    $item
                )
                    ->setMaxLength($this->getRecordNameFieldMaxLength())
                    ->setDisposition('informative');
            }

            $output = $this->nameRecord($item);

            if ($fieldName == 'slug') {
                $output = Html::{'samp'}($output);
            }

            return $output;
        });
    }

    public function defineSlugField($list, $mode)
    {
        $list->addField('slug', function ($item) {
            return Html::{'samp'}($item['slug']);
        });
    }

    public function defineWeightField($list, $mode)
    {
        $list->addField('weight', $mode == 'list' ? '#' : $this->_('Order number'));
    }

    public function defineUrlField($list, $mode)
    {
        $list->addField('url', function ($item) use ($mode) {
            $url = $item['url'];

            if ($url === null) {
                return $url;
            }

            if ($mode == 'list') {
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

    public function defineWebsiteField($list, $mode)
    {
        $list->addField('website', function ($item) use ($mode) {
            $url = $item['website'];

            if ($url === null) {
                return $url;
            }

            if ($mode == 'list') {
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

    public function defineUserField($list, $mode)
    {
        $list->addField('user', function ($item) {
            return $this->apex->component('~admin/users/clients/UserLink', $item['user']);
        });
    }

    public function defineOwnerField($list, $mode)
    {
        $list->addField('owner', function ($item) {
            return $this->apex->component('~admin/users/clients/UserLink', $item['owner']);
        });
    }

    public function defineEmailField($list, $mode)
    {
        $list->addField('email', function ($item) {
            return $this->html->mailLink($item['email']);
        });
    }

    public function defineCreationDateField($list, $mode)
    {
        $list->addField('creationDate', $this->_('Created'), function ($item) use ($mode) {
            if ($mode == 'list') {
                return Html::$time->sinceAbs($item['creationDate']);
            } else {
                return Html::$time->since($item['creationDate']);
            }
        });
    }

    public function defineModificationDateField($list, $mode)
    {
        $list->addField('modificationDate', $this->_('Modified'), function ($item) use ($mode) {
            if ($mode == 'list') {
                return Html::$time->sinceAbs($item['modificationDate']);
            } else {
                return Html::$time->since($item['modificationDate']);
            }
        });
    }

    public function defineLastEditDateField($list, $mode)
    {
        $list->addField('lastEditDate', $this->_('Edited'), function ($item) use ($mode) {
            if ($mode == 'list') {
                return Html::$time->sinceAbs($item['lastEditDate']);
            } else {
                return Html::$time->since($item['lastEditDate']);
            }
        });
    }

    public function defineIsLiveField($list, $mode)
    {
        $list->addField('isLive', $this->_('Live'), function ($item, $context) {
            if (!$item['isLive']) {
                $context->getRowTag()->addClass('disabled');
            }

            return $this->html->booleanIcon($item['isLive']);
        });
    }

    public function defineLiveField($list, $mode)
    {
        $list->addField('live', $this->_('Live'), function ($item, $context) {
            if (!$item['live']) {
                $context->getRowTag()->addClass('disabled');
            }

            return $this->html->booleanIcon($item['live']);
        });
    }

    public function definePriorityField($list, $mode)
    {
        $list->addField('priority', function ($item) {
            $priority = PriorityUnit::factory($item['priority']);
            return $this->html->icon('priority-' . $priority->getOption(), $priority->getLabel())
                ->addClass('priority-' . $priority->getOption());
        });
    }

    public function defineArchiveDateField($list, $mode)
    {
        $list->addField('archiveDate', $this->_('Archive'), function ($item, $context) use ($mode) {
            $date = $item['archiveDate'];
            $hasDate = (bool)$date;
            $isPast = $hasDate && $date->isPast();

            if ($isPast && $mode == 'list') {
                $context->getRowTag()->addClass('inactive');
            }

            $output = Html::$time->date($date);

            if ($output) {
                if ($isPast) {
                    $output->addClass('negative');
                } elseif ($date->lt('+1 month')) {
                    $output->addClass('warning');
                } else {
                    $output->addClass('positive');
                }
            }

            return $output;
        });
    }

    public function defineColorField($list, $mode)
    {
        $list->addField('color', function ($item, $context) {
            try {
                $color = Color::create($item['color']);
            } catch (Throwable $e) {
                return $item['color'];
            }

            return Html::{'span'}($item['color'])
                ->setStyle('background', $item['color'])
                ->setStyle('color', $color->getTextContrastColor())
                ->setStyle('padding', '0 0.6em');
        });
    }

    public function defineEnvironmentModeField($list, $mode)
    {
        $list->addField('environmentMode', $mode == 'list' ? $this->_('Env.') : null, function ($mail) use ($mode) {
            switch ($mail['environmentMode']) {
                case 'development':
                    return Html::{'span.priority-low.inactive'}($mode == 'list' ? $this->_('Dev') : $this->_('Development'));
                case 'testing':
                    return Html::{'span.priority-medium.inactive'}($mode == 'list' ? $this->_('Test') : $this->_('Testing'));
                case 'production':
                    return Html::{'span.priority-high.active'}($mode == 'list' ? $this->_('Prod') : $this->_('Production'));
            }
        });
    }

    public function defineAddressField($list, $mode)
    {
        if ($mode == 'list') {
            $list->addField('address', function ($item) {
                if (!$addr = $item['address']) {
                    return;
                }

                if (!$addr instanceof PostalAddress) {
                    return $addr;
                }

                return Html::{'span'}(
                    $addr->getLocality() . ', ' . $addr->getCountryName(),
                    ['title' => str_replace("\n", ', ', $addr->toString())]
                );
            });
        } else {
            $list->addField('address', function ($item) {
                return $this->html->address($item['address']);
            });
        }
    }

    public function defineActionsField($list, $mode)
    {
        $list->addField('actions', function ($item) {
            yield from $this->generateRecordOperativeLinks($item);
        });
    }
}
