<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire\block;

use df;
use df\core;
use df\fire;
use df\flex;
use df\arch;
use df\aura;

use DecodeLabs\Tagged\Xml\Element as XmlElement;
use DecodeLabs\Tagged\Xml\Writer as XmlWriter;
use DecodeLabs\Tagged\Xml\Serializable as XmlSerializable;

class Heading extends Base implements XmlSerializable
{
    const DEFAULT_CATEGORIES = ['Description'];

    const OPTIONS = [
        1 => 'h1',
        2 => 'h2',
        3 => 'h3',
        4 => 'h4',
        5 => 'h5',
        6 => 'h6'
    ];

    protected $_heading;
    protected $_level = 3;
    protected $_class;
    protected $_classOptions;

    public function getDisplayName(): string
    {
        return 'Heading';
    }

    public function getFormat(): string
    {
        return 'structure';
    }

    // Heading
    public function setHeading($heading)
    {
        $this->_heading = $heading;
        return $this;
    }

    public function getHeading()
    {
        return $this->_heading;
    }

    public function setHeadingLevel($level)
    {
        $this->_level = (int)$level;

        if ($this->_level < 1) {
            $this->_level = 1;
        } elseif ($this->_level > 6) {
            $this->_level = 6;
        }

        return $this;
    }

    public function getHeadingLevel()
    {
        return $this->_level;
    }

    public function setHeadingClass(?string $class)
    {
        $this->_class = $class;
        return $this;
    }

    public function getHeadingClass(): ?string
    {
        return $this->_class;
    }

    public function setClassOptions(?array $options)
    {
        $this->_classOptions = $options;
        return $this;
    }

    public function getClassOptions(): ?array
    {
        return $this->_classOptions;
    }


    // IO
    public function isEmpty(): bool
    {
        return !strlen(trim($this->_heading));
    }

    public function getTransitionValue()
    {
        return $this->_heading;
    }

    public function setTransitionValue($value)
    {
        $this->_heading = str_replace("\n", ' ', $value);
        return $this;
    }


    protected function readXml(XmlElement $element): void
    {
        $this->_heading = $element->getFirstCDataSection();
        $this->_level = $element['level'];
        $this->_class = $element['class'];
    }

    protected function writeXml(XmlWriter $writer): void
    {
        $writer['level'] = $this->_level;

        if ($this->_class !== null) {
            $writer['class'] = $this->_class;
        }

        $writer->writeCData($this->_heading);
    }


    // Render
    public function render()
    {
        return $this->getView()->html('h'.$this->_level.'.block', $this->_heading)
            ->addClass($this->_class)
            ->setDataAttribute('type', $this->getName());
    }



    // Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate
    {
        return new class($this, ...func_get_args()) extends Base_Delegate {
            protected function setDefaultValues()
            {
                $this->values->heading = $this->_block->getHeading();
                $this->values->level = $this->_block->getHeadingLevel();
                $this->values->class = $this->_block->getHeadingClass();
            }

            public function renderFieldContent(aura\html\widget\Field $field)
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
                $classes = $this->_block->getClassOptions();

                if (!empty($classes)) {
                    $current = $this->values['class'];

                    if (!empty($current) && !isset($classes[$current])) {
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

                return $this;
            }

            public function apply()
            {
                $this->data->newValidator()
                    ->addRequiredField('heading', 'text')
                    ->addRequiredField('level', 'integer')
                        ->setRange(1, 6)
                    ->addField('class', 'text')
                    ->validate($this->values);

                $this->_block->setHeading($this->values['heading']);
                $this->_block->setHeadingLevel($this->values['level']);
                $this->_block->setHeadingClass($this->values['class']);

                return $this->_block;
            }
        };
    }
}
