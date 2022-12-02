<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block\LibraryImage;

use DecodeLabs\Coercion;
use DecodeLabs\Dictum;

use DecodeLabs\R7\Legacy;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\Block\LibraryImage;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use df\arch\node\form\SelectorDelegate;
use df\aura\html\widget\Field as FieldWidget;

/**
 * @extends BlockDelegateAbstract<LibraryImage>
 */
class FormDelegate extends BlockDelegateAbstract
{
    /**
     * @var LibraryImage
     */
    protected Block $block;

    protected function loadDelegates(): void
    {
        $this->loadDelegate('image', '~admin/media/ImageSelector')
            ->as(SelectorDelegate::class)
            ->isForOne(true)
            ->isRequired(true);
    }

    protected function setDefaultValues(): void
    {
        $this['image']->as(SelectorDelegate::class)
            ->setSelected($this->block->getImageId());

        $this->values->alt = $this->block->getAltText();
        $this->values->link = $this->block->getLink();
        $this->values->width = $this->block->getWidth();
    }

    public function renderFieldContent(FieldWidget $field): void
    {
        $fa = $field->addField($this->_('Library image'))->push($this['image']);

        $image = $this['image']->as(SelectorDelegate::class);

        if ($image->hasSelection()) {
            $fileId = Coercion::toString($image->getSelected());

            $fa->add(
                'div.link',
                $this->html->link(
                    $this->context->uri(
                        '~admin/media/files/edit?file=' . $fileId,
                        '~admin/media/files/details?file=' . $fileId
                    ),
                    $this->_('Edit file details')
                )
                    ->setIcon('edit')
                    ->setTarget('_blank')
            );
        }

        // Alt text
        $field->addField($this->_('Alt text'))->push(
            $this->html->textbox($this->fieldName('alt'), $this->values->alt)
                ->isRequired(true)
                ->setId($this->elementId('alt')),
            $this->html->eventButton($this->eventName('useFileName'), 'Import file name')
                ->setIcon('edit')
                ->setDisposition('operative')
                ->shouldValidate(false)
        );

        // Link URL
        $field->addField($this->_('Link URL'))->push(
            $this->html->textbox($this->fieldName('link'), $this->values->link)
        );

        // Width
        $field->addField($this->_('Width'))->push(
            $this->html->numberTextbox($this->fieldName('width'), $this->values->width)
                ->setStep(0.1)
                ->setMin(0.1)
                ->setMax(100),
            ' %'
        );
    }

    protected function onUseFileNameEvent(): mixed
    {
        $val = $this->data->newValidator()
            // Image
            ->addField('image', 'delegate')
                ->fromForm($this)
            ->validate($this->values);

        if ($val['image']) {
            $fileName = $this->data->media->file->select('fileName')
                ->where('id', '=', $val['image'])
                ->toValue();

            $fileName = trim((string)$fileName);
            $parts = explode('.', $fileName);
            array_pop($parts);
            $fileName = implode(' ', $parts);
            $alt = Dictum::name($fileName);

            $this->values->alt = $alt;
        }

        return Legacy::$http->redirect('#' . $this->elementId('alt'));
    }

    public function apply(): Block
    {
        $validator = $this->data->newValidator()
            // Image
            ->addField('image', 'delegate')
                ->fromForm($this)

            // Alt
            ->addRequiredField('alt', 'text')

            // Link
            ->addField('link', 'text')

            // Width
            ->addField('width', 'floatingPoint')
                ->setRange(0.1, 100)

            ->validate($this->values);

        $this->block->setImageId($validator['image']);
        $this->block->setAltText($validator['alt']);
        $this->block->setLink($validator['link']);
        $this->block->setWidth($validator['width']);

        return $this->block;
    }
}
