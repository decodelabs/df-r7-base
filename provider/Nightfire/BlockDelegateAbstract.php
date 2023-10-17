<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire;

use df\arch\IContext as Context;
use df\arch\node\form\Delegate as FormDelegate;
use df\arch\node\form\State as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\arch\node\TForm_InlineFieldRenderableDelegate;
use df\core\constraint\TRequirable;

/**
 * @template TBlock of Block
 */
abstract class BlockDelegateAbstract extends FormDelegate implements BlockDelegate
{
    use TForm_InlineFieldRenderableDelegate;
    use TRequirable;

    protected bool $_isNested = false;

    /**
     * @phpstan-var TBlock
     */
    protected Block $block;

    /**
     * @param TBlock $block
     */
    public function __construct(
        Block $block,
        Context $context,
        FormState $state,
        FormEventDescriptor $event,
        string $id
    ) {
        $this->block = $block;
        parent::__construct($context, $state, $event, $id);
    }

    /**
     * @param TBlock $block
     */
    public function setBlock(Block $block): static
    {
        $this->block = $block;
        return $this;
    }

    /**
     * @return TBlock
     */
    public function getBlock(): Block
    {
        return $this->block;
    }


    /**
     * Set nested
     */
    public function setNested(bool $nested): static
    {
        $this->_isNested = $nested;
        return $this;
    }

    /**
     * Is nested
     */
    public function isNested(): bool
    {
        return $this->_isNested;
    }
}
