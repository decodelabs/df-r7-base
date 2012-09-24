<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;


trait TStructure {

}    



// Description
trait TStructure_Description {

	protected $_title;
	protected $_description;

	public function setTitle($title) {
		$this->_title = $this->_normalizeText($title);
		return $this;
	}

	public function getTitle() {
		return $this->_title;
	}

	public function setDescription($description) {
		$this->_description = $this->_normalizeText($description);
		return $this;
	}

	public function getDescription() {
		return $this->_description;
	}
}



// Metadata
trait TStructure_Metadata {

	protected $_metadata;

	public function setMetadata($metadata) {
		$this->_metadata = $this->_normalizeText($metadata);
		return $this;
	}

	public function getMetadata() {
		return $this->_metadata;
	}
}



// Container
trait TStructure_Container {

	use TStructure_Description;

	protected $_children = array();

	public function readXml(\XMLReader $reader) {
		while($reader->read()) {
			switch($reader->nodeType) {
				case \XMLReader::ELEMENT:
					if($child = $this->_xmlToObject($reader, $this)) {
						$this->addChild($child);
					}

					break;

				case \XMLReader::END_ELEMENT:
					break 2;
			}
		}

		return $this;
	}

	public function writeXml(IDocument $document, \XMLWriter $writer) {
		$writer->startElement($this->getElementName());

		foreach($this->prepareAttributes($document) as $key => $value) {
			$writer->writeAttribute($key, $value);
		}


		// Description
		if($this instanceof IDescriptionProvider) {
			if($this->_title) {
				$writer->writeElement('title', $this->_title);
			}

			if($this->_description) {
				$writer->writeElement('desc', $this->_description);
			}
		}

		// Metadata
		if($this instanceof IMetadataProvider) {
			if($this->_metadata) {
				$writer->startElement('metadata');
				$writer->writeRaw(rtrim($this->_metadata)."\n    ");
				$writer->endElement();
			}
		}

		foreach($this->_children as $child) {
			$child->writeXml($document, $writer);
		}

		$writer->endElement();
		return $this;
	}

	public function setChildren(array $children) {
		$this->_chilren = array();
		return $this->addChildren($children);
	}

	public function addChildren(array $children) {
		foreach($children as $child) {
			if(!$child instanceof IElement) {
				throw new InvalidArgumentException(
					'Invalid child element detected'
				);
			}

			$this->addChild($child);
		}

		return $this;
	}

	public function addChild(IElement $element) {
		if(!in_array($element, $this->_children, true)) {
			$this->_children[] = $element;
		}

		return $this;
	}

	public function getChildren() {
		return $this->_children;
	}

	public function removeChild(IElement $element) {
		foreach($this->_children as $i => $child) {
			if($element === $child) {
				unset($this->_children[$i]);
				break;
			}
		}

		return $this;
	}

	public function clearChildren() {
		$this->_children = array();
		return $this;
	}


	public function toPath() {
		$output = null;
		$attributes = $this->getGraphicalAttributes();

		foreach($this->_children as $child) {
			if(!$child instanceof IPathProvider) {
				throw new RuntimeException(
					'Cannot create compound path from '.$child->getName().' elements'
				);
			}

			$path = $child->toPath();
			$attributes = array_merge($attributes, $child->getGraphicalAttributes());

			if($child instanceof ITransformAttributeModule 
		    && null !== ($transform = $child->getTransform())) {
				// TODO: apply transforms

				throw new RuntimeException(
					'Child path provider has transformation - I\'m afraid I don\'t know how to convert transformations yet!'
				);
			}

			if($output === null) {
				$output = $path;
			} else {
				$output->importPathData($path);
			}
		}

		$output->applyInputAttributes($attributes);

		if($this instanceof ITransformAttributeModule 
	    && null !== ($transform = $this->getTransform())) {
			// TODO: apply parent transforms

			throw new RuntimeException(
				'Path provider has transformation - I\'m afraid I don\'t know how to convert transformations yet!'
			);
		}

		return $output;
	}


	public function getDumpProperties() {
		$output = $this->_attributes;
		
		if($this->_title) {
			$output['title'] = $this->_title;
		}

		if($this->_description) {
			$output['description'] = $this->_description;
		}

		if($this instanceof IMetadataProvider && $this->_metadata) {
			$output['metadata'] = $this->_metadata;
		}

		if(!empty($this->_children)) {
			$output['children'] = $this->_children;

			if(count($output) == 1) {
				$output = $this->_children;
			}
		}

		return $output;
	}
}




// Definitions
trait TStructure_Definitions {

	protected $_definitionsElement;

	public function getDefinitionsElement() {
		$output = null;

		if($this instanceof IContainer) {
			foreach($this->_children as $child) {
				if($child instanceof IDefinitionContainer) {
					$output = $child;
					break;
				}
			}

			if($output === null) {
				$output = new Definitions();
				array_unshift($this->_children, $output);
			}
		}


		if($output === null) {
			$output = new Definitions();
		}

		$this->_definitionsElement = $output;
		return $output;
	}

	public function setDefinitions(array $defs) {
		$this->getDefinitionsElement()->setDefinitions($defs);
		return $this;
	}

	public function addDefinitions(array $defs) {
		$this->getDefinitionsElement()->addDefinitions($defs);
		return $this;
	}

	public function addDefinition(IElement $element) {
		$this->getDefinitionsElement()->addDefinition($element);
		return $this;
	}

	public function getDefinitions() {
		return $this->getDefinitionsElement()->getDefinitions();
	}

	public function removeDefinition(IElement $element) {
		$this->getDefinitionsElement()->removeDefinition($element);
		return $this;
	}

	public function clearDefinitions() {
		$this->getDefinitionsElement()->clearDefinitions();
		return $this;
	}
}