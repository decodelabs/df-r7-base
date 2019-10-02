<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg;

use df;
use df\core;
use df\neon;
use df\flex;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

trait TStructure
{
}



// Description
trait TStructure_Description
{
    protected $_title;
    protected $_description;

    public function setTitle(?string $title)
    {
        $this->_title = $this->_normalizeText($title);
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->_title;
    }

    public function setDescription($description)
    {
        $this->_description = $this->_normalizeText($description);
        return $this;
    }

    public function getDescription()
    {
        return $this->_description;
    }
}



// MetaData
trait TStructure_MetaData
{
    protected $_metaData;

    public function setMetaData($metaData)
    {
        $this->_metaData = $this->_normalizeText($metaData);
        return $this;
    }

    public function getMetaData()
    {
        return $this->_metaData;
    }
}



// Container
trait TStructure_Container
{
    use TStructure_Description;

    protected $_children = [];

    public function readXml(flex\xml\IReadable $reader)
    {
        foreach ($reader->getChildren() as $child) {
            if ($childObject = $this->_xmlToObject($child, $this)) {
                $this->addChild($childObject);
            }
        }

        return $this;
    }

    public function writeXml(flex\xml\IWritable $writer)
    {
        $document = $writer->getRootInterchange();

        $writer->startElement($this->getElementName());
        $writer->setAttributes($this->prepareAttributes($document));

        // Description
        if ($this instanceof IDescriptionProvider) {
            if ($this->_title) {
                $writer->writeElement('title', $this->_title);
            }

            if ($this->_description) {
                $writer->writeElement('desc', $this->_description);
            }
        }

        // MetaData
        if ($this instanceof IMetaDataProvider) {
            if ($this->_metaData) {
                $writer->startElement('metadata');
                $writer->writeRaw(rtrim($this->_metaData)."\n    ");
                $writer->endElement();
            }
        }

        foreach ($this->_children as $child) {
            $child->writeXml($writer);
        }

        $writer->endElement();
        return $this;
    }

    public function setChildren(array $children)
    {
        $this->_chilren = [];
        return $this->addChildren($children);
    }

    public function addChildren(array $children)
    {
        foreach ($children as $child) {
            if (!$child instanceof IElement) {
                throw new InvalidArgumentException(
                    'Invalid child element detected'
                );
            }

            $this->addChild($child);
        }

        return $this;
    }

    public function addChild(IElement $element)
    {
        if (!in_array($element, $this->_children, true)) {
            $this->_children[] = $element;
        }

        return $this;
    }

    public function getChildren()
    {
        return $this->_children;
    }

    public function removeChild(IElement $element)
    {
        foreach ($this->_children as $i => $child) {
            if ($element === $child) {
                unset($this->_children[$i]);
                break;
            }
        }

        return $this;
    }

    public function clearChildren()
    {
        $this->_children = [];
        return $this;
    }


    public function toPath()
    {
        $output = null;
        $attributes = $this->getGraphicalAttributes();

        foreach ($this->_children as $child) {
            if (!$child instanceof IPathProvider) {
                throw new RuntimeException(
                    'Cannot create compound path from '.$child->getName().' elements'
                );
            }

            $path = $child->toPath();
            $attributes = array_merge($attributes, $child->getGraphicalAttributes());

            if ($child instanceof ITransformAttributeModule
            && null !== ($transform = $child->getTransform())) {
                // TODO: apply transforms

                throw new RuntimeException(
                    'Child path provider has transformation - I\'m afraid I don\'t know how to convert transformations yet!'
                );
            }

            if ($output === null) {
                $output = $path;
            } else {
                $output->importPathData($path);
            }
        }

        $output->applyInputAttributes($attributes);

        if ($this instanceof ITransformAttributeModule
        && null !== ($transform = $this->getTransform())) {
            // TODO: apply parent transforms

            throw new RuntimeException(
                'Path provider has transformation - I\'m afraid I don\'t know how to convert transformations yet!'
            );
        }

        return $output;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setSectionVisible('meta', true);

        foreach ($this->_attributes as $key => $value) {
            $entity->setMeta($key, $inspector($value));
        }

        if ($this->_title) {
            $entity->setProperty('*title', $inspector($this->_title));
        }

        if ($this->_description) {
            $entity->setProperty('*description', $inspector($this->_description));
        }

        if ($this instanceof IMetaDataProvider && $this->_metaData) {
            $entity->setProperty('*metadata', $inspector($this->_metaData));
        }

        if (!empty($this->_children)) {
            $entity->setValues($inspector->inspectList($this->_children));
        }
    }
}




// Definitions
trait TStructure_Definitions
{
    protected $_definitionsElement;

    public function getDefinitionsElement()
    {
        $output = null;

        if ($this instanceof IContainer) {
            foreach ($this->_children as $child) {
                if ($child instanceof IDefinitionContainer) {
                    $output = $child;
                    break;
                }
            }

            if ($output === null) {
                $output = new Definitions();
                array_unshift($this->_children, $output);
            }
        }


        if ($output === null) {
            $output = new Definitions();
        }

        $this->_definitionsElement = $output;
        return $output;
    }

    public function setDefinitions(array $defs)
    {
        $this->getDefinitionsElement()->setDefinitions($defs);
        return $this;
    }

    public function addDefinitions(array $defs)
    {
        $this->getDefinitionsElement()->addDefinitions($defs);
        return $this;
    }

    public function addDefinition(IElement $element)
    {
        $this->getDefinitionsElement()->addDefinition($element);
        return $this;
    }

    public function getDefinitions()
    {
        return $this->getDefinitionsElement()->getDefinitions();
    }

    public function removeDefinition(IElement $element)
    {
        $this->getDefinitionsElement()->removeDefinition($element);
        return $this;
    }

    public function clearDefinitions()
    {
        $this->getDefinitionsElement()->clearDefinitions();
        return $this;
    }
}
