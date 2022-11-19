<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Section;

use df\arch\navigation\menu\IEntryList as MenuEntryList;
use df\arch\node\INode as Node;
use df\arch\Scaffold;
use df\aura\view\IView as View;

interface Provider extends Scaffold
{
    // Node loaders
    public function loadSectionNode(): ?Node;
    public function buildSection(string $name, callable $builder, ?callable $linkBuilder = null): View;

    // Section info
    public function getDefaultSection(): string;
    public function getSectionManifest(): array;
    public function isValidSection(string $section): bool;
    public function getSectionItemCounts(): array;

    // Link handlers
    public function generateSectionOperativeLinks(): iterable;
    public function generateSectionSubOperativeLinks(): iterable;
    public function generateSectionTransitiveLinks(): iterable;
    public function generateSectionSectionLinks(): iterable;
    public function generateSectionsMenu(MenuEntryList $entryList): void;
}
