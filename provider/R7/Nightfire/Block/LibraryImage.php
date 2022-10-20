<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Nightfire\Block;

use df\arch;
use df\aura;

use df\arch\IContext as Context;
use df\arch\node\IDelegate as NodeDelegate;
use df\arch\node\IFormState as FormState;
use df\arch\node\IFormEventDescriptor as FormEventDescriptor;
use df\arch\scaffold\Node\Form\SelectorDelegate;
use df\aura\html\widget\Field as FieldWidget;

use DecodeLabs\Dictum;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;
use DecodeLabs\R7\Legacy;
use DecodeLabs\R7\Nightfire\Block;
use DecodeLabs\R7\Nightfire\BlockAbstract;
use DecodeLabs\R7\Nightfire\BlockDelegateAbstract;
use DecodeLabs\Tagged as Html;
use DecodeLabs\Tagged\Markup;

class LibraryImage extends BlockAbstract
{
    public const DEFAULT_CATEGORIES = ['Description'];

    protected ?string $imageId = null;
    protected ?string $alt = null;
    protected ?string $link = null;

    public function getFormat(): string
    {
        return 'image';
    }

    // Image

    /**
     * @return $this
     */
    public function setImageId(?string $id): static
    {
        $this->imageId = $id;
        return $this;
    }

    public function getImageId(): ?string
    {
        return $this->imageId;
    }


    // Alt

    /**
     * @return $this
     */
    public function setAltText(?string $alt): static
    {
        $this->alt = $alt;
        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->alt;
    }


    // Link

    /**
     * @return $this
     */
    public function setLink(?string $link): ?static
    {
        $this->link = $link;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }


    // IO
    public function isEmpty(): bool
    {
        return empty($this->imageId);
    }

    protected function readXml(XmlElement $element): void
    {
        $this->imageId = $element->getAttribute('image');
        $this->alt = $element->getAttribute('alt');
        $this->setLink($element->getAttribute('href'));
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['image'] = $this->imageId;
        $writer['alt'] = $this->alt;

        if ($this->link) {
            $writer['href'] = $this->link;
        }
    }


    // Render
    public function render(): ?Markup
    {
        $view = $this->getView();

        $url = $view->media->getImageUrl($this->imageId);
        $output = Html::image($url, $this->alt);

        if ($this->link) {
            $output = $view->html->link($this->link, $output);
        }

        $output
            ->addClass('block')
            ->setDataAttribute('type', $this->getName());

        return $output;
    }


    // Form
    public function loadFormDelegate(
        Context $context,
        FormState $state,
        FormEventDescriptor $event,
        string $id
    ): NodeDelegate {
        /**
         * @extends BlockDelegateAbstract<LibraryImage>
         */
        return new class ($this, ...func_get_args()) extends BlockDelegateAbstract {
            /**
             * @var LibraryImage
             */
            protected Block $_block;

            protected function loadDelegates(): void
            {
                /** @var SelectorDelegate */
                $image = $this->loadDelegate('image', '~admin/media/ImageSelector');
                $image
                    ->isForOne(true)
                    ->isRequired(true);
            }

            protected function setDefaultValues(): void
            {
                /** @var SelectorDelegate */
                $image = $this['image'];
                $image->setSelected($this->_block->getImageId());

                $this->values->alt = $this->_block->getAltText();
                $this->values->link = $this->_block->getLink();
            }

            public function renderFieldContent(FieldWidget $field): void
            {
                $fa = $field->addField($this->_('Library image'))->push($this['image']);

                /** @var SelectorDelegate */
                $image = $this['image'];

                if ($image->hasSelection()) {
                    $fileId = $image->getSelected();

                    $fa->add(
                        'div.link',
                        $this->html->link(
                                $this->context->uri('~admin/media/files/edit?file='.$fileId, '~admin/media/files/details?file='.$fileId),
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

                return Legacy::$http->redirect('#'.$this->elementId('alt'));
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

                    ->validate($this->values);

                $this->_block->setImageId($validator['image']);
                $this->_block->setAltText($validator['alt']);
                $this->_block->setLink($validator['link']);

                return $this->_block;
            }
        };
    }
}
