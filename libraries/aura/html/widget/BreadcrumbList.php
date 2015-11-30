<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class BreadcrumbList extends Base implements IListWidget, core\IDumpable {

    use TWidget_NavigationEntryController;

    const PRIMARY_TAG = 'nav';
    const DEFAULT_LINK_WIDGET = 'Link';
    const ENFORCE_DEFAULT_LINK_WIDGET = false;

    protected $_separator = '>';

    public function __construct(arch\IContext $context, $input=null) {
        parent::__construct($context);
        $this->_entries = new aura\html\ElementContent();

        if($input === true || $input === 'sitemap') {
            $input = null;
            $this->addSitemapEntries();
        } else if(is_string($input) || $input instanceof arch\IRequest) {
            $request = arch\Request::factory($input);
            $input = null;
            $this->generateFromRequest($request);
        }

        if($input !== null) {
            $this->addEntries($input);
        }
    }

    protected function _render() {
        if(!$this->_renderIfEmpty && $this->_entries->isEmpty()) {
            return null;
        }

        $tag = $this->getTag()->shouldRenderIfEmpty($this->_renderIfEmpty);
        $content = new aura\html\ElementContent();

        $content->push(
            $containerTag = new aura\html\Element('span', null, [
                'itemscope' => null,
                'itemtype' => 'http://data-vocabulary.org/Breadcrumb'
            ])
        );

        $count = count($this->_entries);

        foreach($this->_entries as $i => $entry) {
            if($entry instanceof ILinkWidget) {
                //$entry->getBodyWrapperTag()->setAttribute('itemprop', 'title');

                $entry->setAttribute('itemprop', 'url');
                $containerTag->push($entry->render());

                if($i < $count - 1) {
                    $oldContainerTag = $containerTag;
                    $oldContainerTag->push(
                        ' ', $this->_separator, ' ',

                        $containerTag = new aura\html\Element('span', null, [
                            'itemscope' => null,
                            'itemprop' => 'child',
                            'itemtype' => 'http://data-vocabulary.org/Breadcrumb'
                        ])
                    );
                }
            } else {
                continue;
            }
        }

        return $this->getTag()->renderWith($content, true);
    }


    public function setSeparator($separator) {
        $this->_separator = $separator;
        return $this;
    }

    public function getSeparator() {
        return $this->_separator;
    }


    public function generateFromRequest(arch\IRequest $request=null) {
        if($request === null) {
            $request = $this->_context->request;
        }

        $entryList = arch\navigation\breadcrumbs\EntryList::generateFromRequest($request);
        $this->setEntries($entryList);

        return $this;
    }

    public function addSitemapEntries() {
        $this->setEntries(
            $this->_context->apex->breadcrumbs()
        );

        return $this;
    }



// Dump
    public function getDumpProperties() {
        return [
            'entries' => $this->_entries,
            'tag' => $this->getTag()
        ];
    }
}
