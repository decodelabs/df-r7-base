<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Section;

use df\arch\IComponent as Component;
use df\arch\scaffold\IScaffold as Scaffold;
use df\arch\node\INode as Node;
use df\aura\view\IView as View;
use df\arch\navigation\menu\IEntryList as MenuEntryList;
use df\aura\html\widget\Menu as MenuWidget;

interface Provider extends Scaffold
{
    // Node loaders
    public function loadSectionNode(): ?Node;
    public function buildSection(string $name, callable $builder, ?callable $linkBuilder=null): View;

    // Section info
    public function getDefaultSection(): string;
    public function getSectionManifest(): array;
    public function getSectionItemCounts(): array;

    // Components
    public function buildSectionHeaderBarComponent(array $args): Component;
    public function renderSectionSelectorArea();

    // Link handlers
    public function generateSectionOperativeLinks(): iterable;
    public function generateSectionSubOperativeLinks(): iterable;
    public function generateSectionTransitiveLinks(): iterable;
    public function generateSectionSectionLinks(): iterable;
    public function generateSectionsMenu(MenuEntryList $entryList): void;
}
