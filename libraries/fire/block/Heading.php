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

class Heading extends Base {

    const OUTPUT_TYPES = ['Html'];
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

    public function getDisplayName() {
        return 'Heading';
    }

    public function getFormat() {
        return 'structure';
    }

// Heading
    public function setHeading($heading) {
        $this->_heading = $heading;
        return $this;
    }

    public function getHeading() {
        return $this->_heading;
    }

    public function setHeadingLevel($level) {
        $this->_level = (int)$level;

        if($this->_level < 1) {
            $this->_level = 1;
        } else if($this->_level > 6) {
            $this->_level = 6;
        }

        return $this;
    }

    public function getHeadingLevel() {
        return $this->_level;
    }

// IO
    public function isEmpty() {
        return !strlen(trim($this->_heading));
    }

    public function getTransitionValue() {
        return $this->_heading;
    }

    public function setTransitionValue($value) {
        $this->_heading = str_replace("\n", ' ', $value);
        return $this;
    }

    public function readXml(flex\xml\IReadable $reader) {
        $this->_validateXmlReader($reader);
        $this->_heading = $reader->getFirstCDataSection();
        $this->_level = $reader->getAttribute('level');

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer) {
        $this->_startWriterBlockElement($writer);

        $writer->setAttribute('level', $this->_level);
        $writer->writeCData($this->_heading);

        $this->_endWriterBlockElement($writer);
        return $this;
    }

// Render
    public function render() {
        return $this->getView()->html('h'.$this->_level.'.block', $this->_heading)
            ->setDataAttribute('type', $this->getName());
    }



// Form
    public function loadFormDelegate(arch\IContext $context, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, string $id): arch\node\IDelegate {
        return new class($this, ...func_get_args()) extends Base_Delegate {

            protected function setDefaultValues() {
                $this->values->heading = $this->_block->getHeading();
                $this->values->level = $this->_block->getHeadingLevel();
            }

            public function renderFieldContent(aura\html\widget\Field $field) {
                $field->push(
                    $this->html->field($this->_('Heading text'))->push(
                        $this->html->selectList($this->fieldName('level'), $this->values->level, Heading::OPTIONS),

                        $this->html->textbox($this->fieldName('heading'), $this->values->heading)
                            ->isRequired($this->_isRequired)
                            ->setPlaceholder('Heading text')
                    )
                );

                return $this;
            }

            public function apply() {
                $this->data->newValidator()
                    ->addRequiredField('heading', 'text')
                    ->addRequiredField('level', 'integer')
                        ->setRange(1, 6)
                    ->validate($this->values);

                $this->_block->setHeading($this->values['heading']);
                $this->_block->setHeadingLevel($this->values['level']);

                return $this->_block;
            }
        };
    }
}
