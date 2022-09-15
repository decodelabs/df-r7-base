<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire;

use df;
use df\core;
use df\fire;
use df\aura;
use df\arch;
use df\apex;
use df\flex;

use DecodeLabs\Exemplar\Serializable as XmlSerializable;

// Manager
interface IManager extends core\IManager
{
    public function getConfig(): Config;

    public function getCategories(): array;
    public function getCategory(?string $name): ?ICategory;

    public function isBlockAvailable(string $name): bool;
    public function getAllBlocks(): array;
    public function getAllBlockNames(): array;
    public function getAllBlockNamesByFormat(): array;
    public function getCategoryBlocks(string $category): array;
    public function getCategoryBlockNames(string $category): array;
    public function getCategoryBlockNamesByFormat(string $category): array;
}



// Block
interface IBlock extends
    XmlSerializable,
    aura\view\IDeferredRenderable,
    arch\node\IDelegateProxy
{
    public static function factory(string $name): static;
    public static function normalize($block): ?static;

    public function getName(): string;
    public function getDisplayName(): string;
    public function isNested(bool $flag=null);

    public function getVersion(): int;
    public function getFormat(): string;
    public static function getDefaultCategories(): array;

    public function isEmpty(): bool;
    public function isHidden(): bool;
    public function getTransitionValue();
    public function setTransitionValue($value);
}

interface IBlockDelegate extends
    arch\node\IDelegate,
    arch\node\IInlineFieldRenderableDelegate,
    arch\node\IResultProviderDelegate
{
}



// Category
interface ICategory extends core\IStringProvider
{
    public static function factory(string $name): ICategory;
    public static function normalize($category): ?ICategory;
    public static function normalizeName($category): ?string;

    public function getName(): string;

    public static function getDefaultBlockTypes(): array;
    public function getDefaultEditorBlockType(): ?string;
    public static function getFormatWeights(): array;

    public function setBlocks(array $blocks);
    public function addBlocks(array $blocks);
    public function addBlock($block);
    public function hasBlock(string $block): bool;
    public function getBlocks(): array;
    public function removeBlock(string $block);
}




// Slots
interface ISlotDefinition extends core\IArrayInterchange
{
    public static function fromArray(array $values): ISlotDefinition;
    public static function createDefault(): ISlotDefinition;

    public function setId(?string $id);
    public function getId(): string;
    public function isPrimary(): bool;

    public function setName(?string $name);
    public function getName(): string;

    public function isStatic(): bool;

    public function setMinBlocks(int $min);
    public function getMinBlocks(): int;
    public function setMaxBlocks(?int $max);
    public function getMaxBlocks(): ?int;
    public function hasBlockLimit(): bool;

    public function setCategory($category);
    public function getCategory(): ?string;
}


interface ISlotContent extends
    core\collection\IAttributeContainer,
    aura\view\IDeferredRenderable,
    XmlSerializable
{
    public function setId(?string $id);
    public function getId(): ?string;
    public function isPrimary(): bool;

    public function isNested(bool $flag=null);
    public function hasChanged(bool $flag=null);

    public function setBlocks(array $blocks);
    public function addBlocks(array $blocks);
    public function setBlock(int $index, IBlock $block);
    public function putBlock(int $index, IBlock $block);
    public function addBlock(IBlock $block);
    public function getBlock(int $index): ?IBlock;
    public function getBlocks(): array;
    public function hasBlock(int $index): bool;
    public function removeBlock(int $index);
    public function clearBlocks();
    public function countBlocks(): int;
}



// Layout
interface ILayoutConfig extends core\IConfig
{
    public function getLayoutList(string $area=null): array;
    public function getLayoutDefinition(string $id): ILayoutDefinition;
    public function isStaticLayout(string $id): bool;
    public function getStaticLayoutDefinition(string $id): ILayoutDefinition;
    public function getAllLayoutDefinitions(): array;
    public function setLayoutDefinition(ILayoutDefinition $definition);
    public function removeLayoutDefinition(string $id);
}


interface ILayoutDefinition
{
    public function setId(?string $id);
    public function getId(): string;

    public function setName(?string $name);
    public function getName(): string;

    public function isStatic(): bool;

    public function setAreas(array $areas);
    public function getAreas();
    public function hasArea(string $area): bool;
    public function hasAreas(): bool;
    public function countAreas(): int;

    public function setSlots(array $slots);
    public function addSlots(array $slots);
    public function addSlot(ISlotDefinition $slot);
    public function getSlots(): array;
    public function getSlot(string $id): ?ISlotDefinition;
    public function removeSlot(string $id);
    public function countSlots(): int;
    public function setSlotOrder(array $ids);
}


interface ILayoutContent extends
    core\collection\IAttributeContainer,
    XmlSerializable
{
    public function setId(?string $id);
    public function getId(): ?string;

    public function setSlots(array $slots);
    public function addSlots(array $slots);
    public function setSlot(ISlotContent $slot);
    public function getSlot(string $id): ?ISlotContent;
    public function getSlots(): array;
    public function hasSlot(string ...$ids): bool;
    public function removeSlot(string $id);
    public function clearSlots();
    public function countSlots(): int;
}

interface ILayoutMap extends aura\view\ILayoutMap
{
    public function getTheme(): aura\theme\ITheme;

    public function setGenerator(callable $generator=null);
    public function getGenerator(): ?callable;

    public function setEntries(array $entries);
    public function addEntries(array $entries);
    public function addEntry(ILayoutMapEntry $entry);
    public function getEntries();
    public function removeEntry(string $id);
    public function clearEntries();
}

interface ILayoutMapEntry
{
    public function getId(): string;
    public function allowsTheme(aura\theme\ITheme $theme);
    public function matches(arch\IRequest $request);
    public function apply(aura\view\ILayoutView $view);
}



// Cache
class Cache extends core\cache\Base
{
}
