<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;
    
class Document implements IDocument, core\IDumpable {

	use TStructure_Container;
	use TStructure_Metadata;
	use TStructure_Definitions;
	use TAttributeModule;
	use TAttributeModule_Structure;
	use TAttributeModule_AspectRatio;
	use TAttributeModule_BaseProfile;
	use TAttributeModule_Dimension;
	use TAttributeModule_DocumentEvents;
	use TAttributeModule_Position;
	use TAttributeModule_ViewBox;
	use TAttributeModule_ZoomAndPan;

	public function getElementName() {
		return 'svg';
	}

	public function setContentScriptType($type) {
		return $this->_setAttribute(
			'contentScriptType',
			$this->_normalizeIdentifier($type)
		);
	}

	public function getContentScriptType() {
		return $this->_getAttribute('contentScriptType');
	}

	public function setContentStyleType($type) {
		return $this->_setAttribute(
			'contentStyleType',
			$this->_normalizeIdentifier($type)
		);
	}

	public function getContentStyleType() {
		return $this->_getAttribute('contentStyleType');
	}

	public function setVersion($version) {
		return $this->_setAttribute(
			'version',
			$this->_normalizeKeyword(
				$version,
				['1.0', '1.1'],
				'version'
			)
		);
	}

	public function getVersion() {
		return $this->_getAttribute('version');
	}

	protected function _getExtraAttributes() {
		return [
			'xmlns' => 'http://www.w3.org/2000/svg',
			'xmlns:xlink' => 'http://www.w3.org/1999/xlink'
		];
	}

	public static function fromXmlString($xml) {
		$reader = new \XMLReader();
		$xml = str_replace('&#', '&amp;#', $xml);
		$reader->xml($xml);
		$reader->setParserProperty(\XMLReader::SUBST_ENTITIES, false);

		while($reader->nodeType !== 1) {
			$reader->read();
		}

		$output = self::_xmlToObject($reader);
		$output->readXml($reader);

		return $output;
	}

	public function toXml($embedded=false) {
		$writer = new \XMLWriter();
		$writer->openMemory();
		$writer->setIndent(true);
		$writer->setIndentString('    ');

		if(!$embedded) {
			$writer->startDocument('1.0', 'UTF-8');
			$writer->writeDTD('svg', '-//W3C//DTD SVG 1.1//EN', 'http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd');
		}

	 	$this->writeXml($this, $writer);

	 	if(!$embedded) {
	 		$writer->endDocument();
	 	}
	 	
	 	return $writer->outputMemory();
	}

	public function rasterize() {
		$xml = $this->toXml();
		$class = neon\raster\Image::getDefaultDriverClass();

		if(!$class::canRead('SVG')) {
			throw new RuntimeException(
				'Current raster driver cannot read SVG format images - you should probably install ImageMagick'
			);
		}

		return neon\raster\Image::loadString($xml);
	}
}