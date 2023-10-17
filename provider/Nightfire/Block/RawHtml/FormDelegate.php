<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\RawHtml;

use DecodeLabs\R7\Nightfire\Block;

use DecodeLabs\R7\Nightfire\Block\RawHtml;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<RawHtml>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var RawHtml
     */
    protected Block $block;

    protected function setDefaultValues(): void
    {
        $this->values->content = $this->block->getHtmlContent();
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        $field->push(
            $this->html->textarea($this->fieldName('content'), $this->values->content)
                ->isRequired($this->_isRequired)
                ->addClass('editor html')
        );
    }

    public function apply(): Block
    {
        $validator = $this->data->newValidator()
            ->addField('content', 'text')
                ->isRequired($this->_isRequired)
            ->validate($this->values);

        $this->block->setHtmlContent($validator['content']);
        return $this->block;
    }
}
