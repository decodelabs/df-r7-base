<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use df\arch\node\IDelegate;
use df\arch\node\IInlineFieldRenderableDelegate;
use df\arch\node\IResultProviderDelegate;

interface BlockDelegate extends
    IDelegate,
    IInlineFieldRenderableDelegate,
    IResultProviderDelegate
{
    /**
     * @return $this
     */
    public function setBlock(Block $block): static;
    public function getBlock(): Block;

    /**
     * @return $this
     */
    public function setNested(bool $nested): static;
    public function isNested(): bool;
}
