<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use df\core\IStringProvider;

interface Category extends IStringProvider
{
    public static function factory(string $name): Category;

    public static function normalize(
        string|Category|null $category
    ): ?Category;

    public static function normalizeName(
        string|Category|null $category
    ): ?string;

    public function getName(): string;

    /**
     * @return array<string>
     */
    public static function getDefaultBlockTypes(): array;
    public function getDefaultEditorBlockType(): ?string;

    /**
     * @return array<string, int>
     */
    public static function getFormatWeights(): array;

    /**
     * @param array<string|Block> $blocks
     * @return $this
     */
    public function setBlocks(array $blocks): static;

    /**
     * @param array<string|Block> $blocks
     * @return $this
     */
    public function addBlocks(array $blocks): static;

    /**
     * @return $this
     */
    public function addBlock(
        string|Block $block
    ): static;

    public function hasBlock(string $block): bool;

    /**
     * @return array<string>
     */
    public function getBlocks(): array;

    /**
     * @return $this
     */
    public function removeBlock(string $block): static;
}
