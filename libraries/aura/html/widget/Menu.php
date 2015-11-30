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

class Menu extends Base implements core\IDumpable {

    use TWidget_NavigationEntryController;

    const PRIMARY_TAG = 'nav';
    const DEFAULT_LINK_WIDGET = 'Link';
    const ENFORCE_DEFAULT_LINK_WIDGET = false;

    public function __construct(arch\IContext $context, $input=null) {
        parent::__construct($context);

        $this->_entries = new aura\html\ElementContent();
        $this->_context = $context;

        if($input !== null) {
            $this->addEntries($input);
        }
    }

    protected function _render() {
        $tag = $this->getTag()->shouldRenderIfEmpty($this->_renderIfEmpty);
        $content = new aura\html\ElementContent();

        foreach($this->_entries as $entry) {
            if($entry instanceof IDescriptionAwareLinkWidget) {
                $entry->shouldShowDescription($this->_showDescriptions);
            }

            $args = [];

            if(($entry instanceof aura\html\ITagDataContainer) && ($id = $entry->getDataAttribute('menuid'))) {
                $args['class'] = 'item-'.$id;
            }

            if($entry instanceof aura\html\widget\Link) {
                $entry->ensureMatchRequest();

                if($entry->isComputedActive()) {
                    if(!isset($args['class'])) {
                        $args['class'] = '';
                    } else {
                        $args['class'] .= ' ';
                    }

                    $args['class'] .= 'active';
                }
            }

            $entry = new aura\html\Element('li', $entry, $args);
            $entry->shouldRenderIfEmpty(false);
            $content->push($entry);
        }

        return $tag->renderWith(
            (new aura\html\Tag('ul'))->shouldRenderIfEmpty($this->_renderIfEmpty)->renderWith($content),
            true
        );
    }


// Dump
    public function getDumpProperties() {
        return [
            'entries' => $this->_entries,
            'tag' => $this->getTag()
        ];
    }
}
