<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;
    
class Document implements IDocument {

	use neon\svg\TAttributeModule;
	use neon\svg\TAttributeModule_AspectRatio;
	use neon\svg\TAttributeModule_BaseProfile;
	use neon\svg\TAttributeModule_Clip;
	use neon\svg\TAttributeModule_Conditional;
	use neon\svg\TAttributeModule_Container;
	use neon\svg\TAttributeModule_Core;
	use neon\svg\TAttributeModule_Cursor;
	use neon\svg\TAttributeModule_Dimension;
	use neon\svg\TAttributeModule_DocumentEvents;
	use neon\svg\TAttributeModule_ExternalResources;
	use neon\svg\TAttributeModule_Filter;
	use neon\svg\TAttributeModule_FilterColor;
	use neon\svg\TAttributeModule_Flood;
	use neon\svg\TAttributeModule_Font;
	use neon\svg\TAttributeModule_Gradient;
	use neon\svg\TAttributeModule_Graphics;
	use neon\svg\TAttributeModule_GraphicalElementEvents;
	use neon\svg\TAttributeModule_Marker;
	use neon\svg\TAttributeModule_Mask;
	use neon\svg\TAttributeModule_Paint;
	use neon\svg\TAttributeModule_PaintOpacity;
	use neon\svg\TAttributeModule_Position;
	use neon\svg\TAttributeModule_Style;
	use neon\svg\TAttributeModule_Text;
	use neon\svg\TAttributeModule_TextContent;
	use neon\svg\TAttributeModule_ViewBox;
	use neon\svg\TAttributeModule_Viewport;
	use neon\svg\TAttributeModule_ZoomAndPan;


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
}