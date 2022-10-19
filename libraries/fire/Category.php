<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire;

use df\core\IStringProvider;

interface Category extends IStringProvider
{
    public static function factory(string $name): Category;
    public static function normalize($category): ?Category;
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
