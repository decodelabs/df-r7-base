<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\VideoEmbed;

use DecodeLabs\Coercion;

use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\Block\VideoEmbed;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<VideoEmbed>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var VideoEmbed
     */
    protected Block $block;

    protected function setDefaultValues(): void
    {
        $this->values->embed = $this->block->getEmbedCode();
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        $field->push(
            $this->html->textarea(
                $this->fieldName('embed'),
                $this->values->embed
            )
                ->isRequired($this->_isRequired)
        );
    }

    public function apply(): Block
    {
        $this->block->setEmbedCode(
            Coercion::toStringOrNull($this->values['embed'])
        );

        return $this->block;
    }
}
