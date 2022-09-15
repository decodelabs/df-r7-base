<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\fire\block;

use DecodeLabs\Dictum;
use df;
use df\core;
use df\fire;
use df\arch;
use df\apex;
use df\flex;
use df\aura;

use DecodeLabs\Tagged as Html;
use DecodeLabs\Exemplar\Element as XmlElement;
use DecodeLabs\Exemplar\Writer as XmlWriter;

class LibraryImage extends Base
{
    public const DEFAULT_CATEGORIES = ['Description'];

    protected $_imageId;
    protected $_alt;
    protected $_link;

    public function getFormat(): string
    {
        return 'image';
    }

    // Image
    public function setImageId($id)
    {
        $this->_imageId = $id;
        return $this;
    }

    public function getImageId()
    {
        return $this->_imageId;
    }


    // Alt
    public function setAltText(?string $alt)
    {
        $this->_alt = $alt;
        return $this;
    }

    public function getAltText(): ?string
    {
        return $this->_alt;
    }


    // Link
    public function setLink(string $link=null)
    {
        $this->_link = $link;
        return $this;
    }

    public function getLink()
    {
        return $this->_link;
    }


    // IO
    public function isEmpty(): bool
    {
        return empty($this->_imageId);
    }

    protected function readXml(XmlElement $element): void
    {
        $this->_imageId = $element['image'];
        $this->_alt = $element['alt'];
        $this->setLink($element['href']);
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['image'] = $this->_imageId;
        $writer['alt'] = $this->_alt;

        if ($this->_link) {
            $writer['href'] = $this->_link;
        }
    }


    // Render
    public function render()
    {
        $view = $this->getView();

        $url = $view->media->getImageUrl($this->_imageId);
        $output = Html::image($url, $this->_alt);

        if ($this->_link) {
            $output = $view->html->link($this->_link, $output);
        }

        $output
            ->addClass('block')
            ->setDataAttribute('type', $this->getName());

        return $output;
    }


    // Form
    public function loadFormDelegate(
        arch\IContext $context,
        arch\node\IFormState $state,
        arch\node\IFormEventDescriptor $event,
        string $id
    ): arch\node\IDelegate {
        return new class ($this, ...func_get_args()) extends Base_Delegate {
            /**
             * @var LibraryImage
             */
            protected $_block;

            protected function loadDelegates()
            {
                /**
                 * Image
                 * @var arch\scaffold\Node\Form\SelectorDelegate $image
                 */
                $image = $this->loadDelegate('image', '~admin/media/ImageSelector');
                $image
                    ->isForOne(true)
                    ->isRequired(true);
            }

            protected function setDefaultValues()
            {
                $this['image']->setSelected($this->_block->getImageId());
                $this->values->alt = $this->_block->getAltText();
                $this->values->link = $this->_block->getLink();
            }

            public function renderFieldContent(aura\html\widget\Field $field)
            {
                $fa = $field->addField($this->_('Library image'))->push($this['image']);

                if ($this['image']->hasSelection()) {
                    $fileId = $this['image']->getSelected();

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

                return $this;
            }

            protected function onUseFileNameEvent()
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

                return $this->http->redirect('#'.$this->elementId('alt'));
            }

            public function apply()
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
