<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use DecodeLabs\Exemplar\Serializable as XmlSerializable;
use DecodeLabs\Tagged\Markup;

use df\arch\node\IDelegateProxy as DelegateProxy;
use df\aura\view\IDeferredRenderable as DeferredRenderable;

interface Block extends
    XmlSerializable,
    DeferredRenderable,
    DelegateProxy
{
    public static function factory(string $name): Block;
    public static function normalize(
        Block|string|null $block
    ): ?Block;

    public function getName(): string;
    public function getDisplayName(): string;

    /**
     * @return $this
     */
    public function setNested(bool $nested): static;
    public function isNested(): bool;

    public function getVersion(): int;
    public function getFormat(): string;

    /**
     * @return array<string>
     */
    public static function getDefaultCategories(): array;

    public function isEmpty(): bool;
    public function isHidden(): bool;

    public function getTransitionValue(): mixed;

    /**
     * @return $this
     */
    public function setTransitionValue(mixed $value): static;

    public function render(): ?Markup;
}
