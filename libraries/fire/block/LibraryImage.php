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

class LibraryImage extends Base
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

    public function readXml(flex\xml\IReadable $reader)
    {
        $this->_validateXmlReader($reader);

        $this->_imageId = $reader->getAttribute('image');
        $this->_alt = $reader->getAttribute('alt');
        $this->setLink($reader->getAttribute('href'));

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer)
    {
        $this->_startWriterBlockElement($writer);

        $writer->setAttribute('image', $this->_imageId);
        $writer->setAttribute('alt', $this->_alt);

        if ($this->_link) {
            $writer->setAttribute('href', $this->_link);
        }

        $this->_endWriterBlockElement($writer);

        return $this;
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
                $field->addField($this->_('Library image'))->push($this['image']);

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
