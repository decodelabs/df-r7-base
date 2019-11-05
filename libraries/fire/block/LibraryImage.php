<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\arch;
use df\flex;
use df\aura;

use DecodeLabs\Tagged\Xml\Element as XmlElement;
use DecodeLabs\Tagged\Xml\Writer as XmlWriter;
use DecodeLabs\Tagged\Xml\Serializable as XmlSerializable;

class LibraryImage extends Base implements XmlSerializable
{
    const DEFAULT_CATEGORIES = ['Description'];

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
        $output = $view->html->image($url, $this->_alt);

        if ($this->_link) {
            $output = $view->html->link($this->_link, $output);
        }

        $output
            ->addClass('block')
            ->setDataAttribute('type', $this->getName());

        return $output;
    }


    // Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate
    {
        return new class($this, ...func_get_args()) extends Base_Delegate {
            protected function loadDelegates()
            {
                $this->loadDelegate('image', '~/media/FileSelector')
                    ->setAcceptTypes('image/*')
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
                                $this->uri('~admin/media/files/edit?file='.$fileId, '~admin/media/files/details?file='.$fileId),
                                $this->_('Edit file details')
                            )
                            ->setIcon('edit')
                            ->setTarget('_blank')
                    );
                }

                // Alt text
                $field->addField($this->_('Alt text'))->push(
                    $this->html->textbox($this->fieldName('alt'), $this->values->alt)
                );

                // Link URL
                $field->addField($this->_('Link URL'))->push(
                    $this->html->textbox($this->fieldName('link'), $this->values->link)
                );

                return $this;
            }

            public function apply()
            {
                $validator = $this->data->newValidator()
                    // Image
                    ->addField('image', 'delegate')
                        ->fromForm($this)

                    // Alt
                    ->addField('alt', 'text')

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
