<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\Error;

use DecodeLabs\Coercion;

use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\Block\Error;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<Error>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var Error
     */
    protected Block $block;

    protected function setDefaultValues(): void
    {
        $this->setStore('type', $this->block->getType());
        $this->setStore('data', $this->block->getData());

        if ($error = $this->block->getError()) {
            $this->setStore('message', $error->getMessage());
        }
    }

    protected function afterInit(): void
    {
        $this->block->setType(Coercion::toStringOrNull($this->getStore('type')));
        $this->block->setData($this->getStore('data'));
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        $output = $this->html->flashMessage($this->_(
            'Error loading block type: ' . $this->getStore('type')
        ), 'error');

        $output->setDescription($this->getStore('message'));
        $this->block->setData($this->getStore('data'));

        $field->push($output);
    }

    public function apply(): Block
    {
        $this->values->addError('noentry', 'Must update block!');
        return $this->block;
    }
}
