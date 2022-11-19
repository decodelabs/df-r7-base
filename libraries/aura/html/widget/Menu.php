<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;

use df\aura;

class Menu extends Base implements Dumpable
{
    use TWidget_NavigationEntryController;

    public const PRIMARY_TAG = 'nav.menu.list';
    public const DEFAULT_LINK_WIDGET = 'Link';
    public const ENFORCE_DEFAULT_LINK_WIDGET = false;

    public function __construct(arch\IContext $context, ...$entries)
    {
        parent::__construct($context);

        $this->_entries = new aura\html\ElementContent();
        $this->_context = $context;

        if (!empty($entries)) {
            $this->addEntries($entries);
        }
    }

    protected function _render()
    {
        $tag = $this->getTag()->shouldRenderIfEmpty($this->_renderIfEmpty);
        $content = new aura\html\ElementContent();

        foreach ($this->_entries as $entry) {
            if ($entry instanceof IDescriptionAwareLinkWidget) {
                $entry->shouldShowDescription($this->_showDescriptions);
            }

            $args = [];

            if (($entry instanceof aura\html\ITagDataContainer) && ($id = $entry->getDataAttribute('menuid'))) {
                $args['class'] = 'item-' . $id;
            }

            if ($entry instanceof aura\html\widget\Link) {
                $entry->ensureMatchRequest();

                if ($entry->isComputedActive()) {
                    if (!isset($args['class'])) {
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


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:%tag' => $this->getTag();
        yield 'values' => $this->_entries->toArray();
    }
}
