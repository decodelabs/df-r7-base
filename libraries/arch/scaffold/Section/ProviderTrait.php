<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Section;

use df\arch\IComponent as Component;
use df\arch\Scaffold;
use df\arch\scaffold\Record\DataProvider as RecordDataProvider;
use df\arch\scaffold\Component\HeaderBar as ScaffoldHeaderBar;
use df\arch\navigation\menu\IEntryList as MenuEntryList;
use df\arch\node\INode as Node;
use df\aura\view\IView as View;
use df\aura\view\content\WidgetContentProvider;
use df\aura\html\widget\Menu as MenuWidget;
use df\core\lang\Callback;

use DecodeLabs\Dictum;

use Throwable;

trait ProviderTrait
{
    // const SECTIONS = [];
    // const DEFAULT_SECTION = 'details';

    private $sectionManifest = null;
    private $sectionItemCounts = null;


    // Node builders
    public function loadSectionNode(): ?Node
    {
        $node = $this->context->request->getNode();
        $sections = $this->getSectionManifest();

        if (!isset($sections[$node])) {
            if ($this->isValidSection($node)) {
                $default = $this->getDefaultSection();

                // Force redirect to details
                if ($default !== $node) {
                    $request = clone $this->context->request;
                    $request->setNode($default);

                    $this->context->forceResponse(
                        $this->context->http->redirect($request)
                    );
                }
            }

            return null;
        }

        return $this->generateNode(function () use ($node) {
            return $this->buildSection($node, function ($record, $view, $scaffold) use ($node) {
                $method = 'render'.ucfirst($node).'SectionBody';

                if (method_exists($this, $method)) {
                    $body = $this->{$method}($record);
                } else {
                    $body = null;
                }

                $breadcrumbs = $this->apex->breadcrumbs();
                $this->updateSectionBreadcrumbs($breadcrumbs, $record, $node);

                return $body;
            });
        });
    }

    public function buildSection(string $name, callable $builder, ?callable $linkBuilder=null): View
    {
        $this->view->setContentProvider(new WidgetContentProvider($this->context));

        $args = [];
        $record = null;

        if ($this instanceof RecordDataProvider) {
            $args[] = $record = $this->getRecord();
        }

        $args[] = $this->view;
        $args[] = $this;

        $hb = $this->apex->component('SectionHeaderBar', $record);

        if ($hb instanceof ScaffoldHeaderBar) {
            $hb->setSubOperativeLinkBuilder($linkBuilder);
        }

        $body = Callback::call($builder, ...$args);
        $this->view->content->push($hb, $body);

        return $this->view;
    }




    // Section info
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

    public function getSectionManifest(): array
    {
        if ($this->sectionManifest === null) {
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
                    $value['name'] = Dictum::name($key);
                } else {
                    $value['name'] = $this->_($value['name']);
                }

                $sections[$key] = $value;
            }

            $this->sectionManifest = $sections;
        }

        return $this->sectionManifest;
    }

    protected function generateSections(): ?array
    {
        if (
            defined('static::SECTIONS') &&
            !empty(static::SECTIONS)
        ) {
            return static::SECTIONS;
        } else {
            return ['details'];
        }
    }

    public function isValidSection(string $section): bool
    {
        if (
            defined('static::SECTIONS') &&
            !empty(static::SECTIONS)
        ) {
            foreach (static::SECTIONS as $key => $value) {
                if (
                    (is_int($key) && $value === $section) ||
                    $key === $section
                ) {
                    return true;
                }
            }

            return false;
        } else {
            return $section === 'details';
        }
    }



    public function getSectionItemCounts(): array
    {
        if ($this->sectionItemCounts === null) {
            try {
                $this->sectionItemCounts = (array)$this->countSectionItems($this->getRecord());
            } catch (Throwable $e) {
                if ($this->app->isDevelopment()) {
                    throw $e;
                }

                $this->sectionItemCounts = [];
            }
        }

        return $this->sectionItemCounts;
    }

    protected function countSectionItems($record): array
    {
        $sections = $this->getSectionManifest();
        unset($sections['details']);
        return $this->countRecordRelations($record, array_keys($sections));
    }




    // Link handlers
    protected function updateSectionBreadcrumbs($breadcrumbs, $record, $node)
    {
        if ($this instanceof RecordDataProvider) {
            $link = $this->getRecordParentUri(/*$record*/ $this->getActiveRow());
        } else {
            $link = $this->getNodeUri('index');
        }

        $breadcrumbs->getEntryByIndex(-2)->setUri($link);
    }



    public function generateSectionOperativeLinks(): iterable
    {
        if ($this instanceof RecordDataProvider) {
            yield from $this->generateRecordOperativeLinks($this->getActiveRow());
        }
    }

    public function generateSectionSubOperativeLinks(): iterable
    {
        $node = $this->context->request->getNode();
        $method = 'generate'.ucfirst($node).'SectionSubOperativeLinks';

        if (method_exists($this, $method)) {
            yield from $this->{$method}();
        }
    }

    public function generateSectionSectionLinks(): iterable
    {
        yield 'sections' => $this->location->getPath()->getDirname().'Sections';
    }

    public function generateSectionsMenu(MenuEntryList $entryList): void
    {
        $counts = $this->getSectionItemCounts();
        $sections = $this->getSectionManifest();
        $i = 0;
        $record = null;

        if ($this instanceof RecordDataProvider) {
            try {
                $record = $this->getRecord();
            } catch (Throwable $e) {
            }
        }

        foreach ($sections as $node => $set) {
            $i++;

            if ($record) {
                $request = $this->getRecordUri($record, $node);
            } else {
                $request = $this->getNodeUri($node);
            }

            $link = $entryList->newLink($request, $set['name'])
                ->setId($node)
                ->setIcon($set['icon'])
                ->setWeight($node == 'details' ? 1 : $i * 10)
                ->setDisposition($set['disposition'] ?? 'informative');

            if (isset($counts[$node])) {
                $link->setNote($this->format->counterNote($counts[$node]));
            }

            $entryList->addEntry($link);
        }
    }

    public function generateSectionTransitiveLinks(): iterable
    {
        $node = $this->context->request->getNode();
        $method = 'generate'.ucfirst($node).'SectionTransitiveLinks';

        if (method_exists($this, $method)) {
            yield from $this->{$method}();
        }
    }
}
