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

use df\arch\scaffold\Record\DataProvider as RecordDataProvider;

use DecodeLabs\Tagged\Html;
use DecodeLabs\Exceptional;

trait TScaffold_SectionProvider
{

    // const SECTIONS = [];

    private $_sections = null;
    private $_sectionItemCounts = null;

    protected function _getSections()
    {
        if ($this->_sections === null) {
            $definition = $this->generateSections();

            if (!is_array($definition) || empty($definition)) {
                $definition = ['details'];
            }

            $sections = [];

            foreach ($definition as $key => $value) {
                if (is_int($key) && is_string($value)) {
                    $key = $value;
                    $value = null;
                }

                if (!is_array($value)) {
                    $value = ['icon' => $value];
                }

                if (!isset($value['icon'])) {
                    $value['icon'] = $key;
                }

                if (!isset($value['name'])) {
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

    protected function generateSections()
    {
        if (defined('static::SECTIONS') && !empty(static::SECTIONS)) {
            return static::SECTIONS;
        } else {
            return ['details'];
        }
    }

    public function loadSectionNode()
    {
        $node = $this->context->request->getNode();
        $sections = $this->_getSections();

        if (isset($sections[$node])) {
            return $this->_generateNode(function () use ($node) {
                $record = null;

                if ($this instanceof RecordDataProvider) {
                    $record = $this->getRecord();
                }

                $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));
                $method = 'render'.ucfirst($node).'SectionBody';

                if (method_exists($this, $method)) {
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

    protected function updateSectionBreadcrumbs($breadcrumbs, $record, $node)
    {
        $breadcrumbs->getEntryByIndex(-2)->setUri($this->uri->directoryRequest($this->getParentSectionRequest()));
    }

    public function buildSection($name, $builder, $linkBuilder=null)
    {
        //$this->view = $this->apex->newWidgetView();
        $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

        $args = [];
        $record = null;

        if ($this instanceof RecordDataProvider) {
            $args[] = $record = $this->getRecord();
        }

        $args[] = $this->view;
        $args[] = $this;

        $hb = $this->apex->component('SectionHeaderBar', $record);

        if ($hb instanceof arch\scaffold\Component\HeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = core\lang\Callback($builder, ...$args);
        $this->view->content->push($hb, $body);

        return $this->view;
    }

    public function buildSectionHeaderBarComponent(array $args)
    {
        return (new arch\scaffold\Component\HeaderBar($this, 'section', $args))
            ->setTitle(
                $this instanceof RecordDataProvider ?
                    [
                        ucfirst($this->getRecordItemName()).': ',
                        $this->getRecordDescription()
                    ] :
                    $this->getDirectoryTitle()
            )
            ->setIcon($this->getRecordIcon())
            ->setBackLinkRequest($this->getParentSectionRequest());
    }

    protected function getParentSectionRequest()
    {
        return $this->getNodeUri('index');
    }

    public function addSectionOperativeLinks($menu, $bar)
    {
        if ($this instanceof RecordDataProvider) {
            $menu->addLinks($this->getRecordOperativeLinks($this->getRecord(), 'sectionHeaderBar'));
        }
    }

    public function addSectionSubOperativeLinks($menu, $bar)
    {
        $node = $this->context->request->getNode();
        $method = 'add'.ucfirst($node).'SectionSubOperativeLinks';

        if (method_exists($this, $method)) {
            $this->{$method}($menu, $bar);
        }
    }

    public function addSectionSectionLinks($menu, $bar)
    {
        $menu->addLinks($this->location->getPath()->getDirname().'Sections');

        if (count($menu->getEntries()) == 1) {
            $menu->clearEntries();
        }
    }

    public function addSectionTransitiveLinks($menu, $bar)
    {
        $node = $this->context->request->getNode();
        $method = 'add'.ucfirst($node).'SectionTransitiveLinks';

        if (method_exists($this, $method)) {
            $this->{$method}($menu, $bar);
        }
    }

    public function generateSectionsMenu($entryList)
    {
        $counts = $this->getSectionItemCounts();
        $sections = $this->_getSections();
        $i = 0;
        $record = null;

        if ($this instanceof RecordDataProvider) {
            try {
                $record = $this->getRecord();
            } catch (\Throwable $e) {
            }
        }

        foreach ($sections as $node => $set) {
            $i++;

            if ($record) {
                $request = $this->getRecordNodeUri($record, $node);
            } else {
                $request = $this->getNodeUri($node);
            }

            $link = $entryList->newLink($request, $set['name'])
                ->setId($node)
                ->setIcon($set['icon'])
                ->setWeight($node == 'details' ? 1 : $i * 10)
                ->setDisposition('informative');

            if (isset($counts[$node])) {
                $link->setNote($this->format->counterNote($counts[$node]));
            }

            $entryList->addEntry($link);
        }
    }

    public function getSectionItemCounts()
    {
        if ($this->_sectionItemCounts === null) {
            try {
                $this->_sectionItemCounts = (array)$this->countSectionItems($this->getRecord());
            } catch (\Throwable $e) {
                if ($this->app->isDevelopment()) {
                    throw $e;
                }

                return [];
            }
        }

        return $this->_sectionItemCounts;
    }

    protected function countSectionItems($record)
    {
        $sections = $this->_getSections();
        unset($sections['details']);
        return $this->countRecordRelations($record, array_keys($sections));
    }


    public function renderSectionSelectorArea($bar)
    {
        $node = $this->context->request->getNode();
        $method = 'render'.ucfirst($node).'SectionSelectorArea';

        if (method_exists($this, $method)) {
            return $this->{$method}($bar);
        }
    }


    public function getDefaultSection(): string
    {
        if (
            defined('static::DEFAULT_SECTION') &&
            static::DEFAULT_SECTION !== null
        ) {
            return static::DEFAULT_SECTION;
        }

        return 'details';
    }
}





// Index header bar provider
trait TScaffold_IndexHeaderBarProvider
{
    public function buildIndexHeaderBarComponent(array $args=null)
    {
        return (new arch\scaffold\Component\HeaderBar($this, 'index', $args))
            ->setTitle($this->getDirectoryTitle())
            ->setBackLinkRequest($this->getIndexBackLinkRequest());
    }

    protected function getIndexBackLinkRequest()
    {
        return $this->uri->backRequest();
    }

    public function buildIndexSection($name, $builder, $linkBuilder=null)
    {
        //$this->view = $this->apex->newWidgetView();
        $this->view->setContentProvider(new aura\view\content\WidgetContentProvider($this->context));

        $args = [$this->view, $this];
        $hb = $this->apex->component('IndexHeaderBar');

        if ($hb instanceof arch\scaffold\Component\HeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = core\lang\Callback($builder, ...$args);
        $this->view->content->push($hb, $body);

        return $this->view;
    }
}

trait TScaffold_RecordIndexHeaderBarProvider
{
    public function addIndexOperativeLinks($menu, $bar)
    {
        if ($this->canAddRecords()) {
            $recordAdapter = $this->getRecordAdapter();

            $menu->addLinks(
                $bar->html->link(
                        $bar->uri($this->getNodeUri('add'), true),
                        $this->_('Add '.$this->getRecordItemName())
                    )
                    ->setIcon('add')
                    ->chainIf($recordAdapter instanceof axis\IUnit, function ($link) use ($recordAdapter) {
                        $link->addAccessLock($recordAdapter->getEntityLocator()->toString().'#add');
                    })
            );
        }
    }
}
