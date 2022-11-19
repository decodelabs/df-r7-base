<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\Markdown;

use DecodeLabs\R7\Nightfire\Block;

use DecodeLabs\R7\Nightfire\Block\Markdown;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<Markdown>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var Markdown
     */
    protected Block $block;

    protected function setDefaultValues(): void
    {
        $this->values->body = $this->block->getBody();
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        $this->view
            ->linkCss('asset://lib/simplemde/simplemde.min.css', 100)
            //->linkJs('asset://lib/simplemde/simplemde.min.js', 100)
            ->dfKit->load('df-kit/markdown')
        ;

        $field->push(
            $ta = $this->html->textarea($this->fieldName('body'), $this->values->body)
                //->isRequired($this->_isRequired)
                ->addClass('editor markdown')
                ->setDataAttribute('editor', 'markdown')
        );
    }

    public function apply(): Block
    {
        $validator = $this->data->newValidator()
            ->addField('body', 'text')
                ->isRequired($this->_isRequired)
            ->validate($this->values);

        $this->block->setBody($validator['body']);
        return $this->block;
    }
}
