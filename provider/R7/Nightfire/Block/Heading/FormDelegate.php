<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\Heading;

use DecodeLabs\Coercion;

use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\Block\Heading;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<Heading>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var Heading
     */
    protected Block $block;

    protected function setDefaultValues(): void
    {
        $this->values->heading = $this->block->getHeading();
        $this->values->level = $this->block->getHeadingLevel();
        $this->values->class = $this->block->getHeadingClass();
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        // Main
        $field->push(
            $inner = $this->html->field()->push(
                $this->html->select($this->fieldName('level'), $this->values->level, Heading::OPTIONS),
                $this->html->textbox($this->fieldName('heading'), $this->values->heading)
                    ->isRequired($this->_isRequired)
                    ->setPlaceholder('Heading text')
            )
        );

        // Class
        $classes = $this->block->getClassOptions();

        if (!empty($classes)) {
            $current = Coercion::toStringOrNull($this->values['class']);

            if (
                !empty($current) &&
                !isset($classes[$current])
            ) {
                $classes[$current] = ucfirst($current);
            }

            $inner->push(
                ' ',
                $this->html->select($this->fieldName('class'), $this->values->class, $classes)
            );
        } else {
            $inner->push(
                ' ',
                $this->html->textbox($this->fieldName('class'), $this->values->class)
                    ->setPlaceholder('class')
                    ->addClass('short')
            );
        }
    }

    public function apply(): Block
    {
        $this->data->newValidator()
            ->addRequiredField('heading', 'text')
            ->addRequiredField('level', 'integer')
                ->setRange(1, 6)
            ->addField('class', 'text')
            ->validate($this->values);

        $this->block->setHeading(Coercion::toStringOrNull($this->values['heading']));
        $this->block->setHeadingLevel(Coercion::toIntOrNull($this->values['level']) ?? 3);
        $this->block->setHeadingClass(Coercion::toStringOrNull($this->values['class']));

        return $this->block;
    }
}
