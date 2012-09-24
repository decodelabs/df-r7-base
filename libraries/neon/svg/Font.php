<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;
    

// Font
class Font implements IFont, core\IDumpable {

	use TCustomContainerElement;
	use TFontFaceContainer;
    use TStructure_Description;
	use TStructure_Metadata;
    use TAttributeModule;
    use TAttributeModule_FontDefinition;
	use TAttributeModule_Structure;

	protected $_missingGlyph;
	protected $_glyphs = array();

	public function setMissingGlyph(IFontGlyph $glyph=null) {
		$this->_missingGlyph = $glyph;
		return $this;
	}

	public function getMissingGlyph() {
		return $this->_missingGlyph;
	}

	public function setGlyphs(array $glyphs) {
		$this->_glyphs = array();
		return $this->addGlyphs($glyphs);
	}

	public function addGlyphs(array $glyphs) {
		foreach($glyphs as $glyph) {
			if(!$glyph instanceof IFontGlyph) {
				throw new InvalidArgumentException(
					'Invalid glyph detected'
				);
			}

			$this->addGlyph($glyph);
		}

		return $this;
	}

	public function addGlyph(IFontGlyph $glyph) {
		$this->_glyphs[] = $glyph;
		return $this;
	}

	public function getGlyphs() {
		return $this->_glyphs;
	}

	public function removeGlyph(IFontGlyph $glyph) {
		foreach($this->_glyphs as $i => $test) {
			if($test === $glyph) {
				unset($this->_glyphs[$i]);
				break;
			}
		}

		return $this;
	}

	public function clearGlyphs() {
		$this->_glyphs = array();
		return $this;
	}

// Dump
	public function getDumpProperties() {
		$output = $this->_attributes;

		if($this->_fontFace) {
			$output['font-face'] = $this->_fontFace;
		}

		if($this->_missingGlyph) {
			$output['missing-glyph'] = $this->_missingGlyph;
		}

		if(!empty($this->_glyphs)) {
			$output['glyphs'] = $this->_glyphs;
		}

		return $output;
	}
}




// Font face
class Font_Face implements IFontFace, core\IDumpable {

	use TCustomContainerElement;
	use TAttributeModule;
	use TAttributeModule_Core;
	use TAttributeModule_Font;

	protected $_sources = array();

	public function setAccentHeight($height) {
		return $this->_setAttribute(
			'accent-height',
			$this->_normalizeLength($height)
		);
	}

	public function getAccentHeight() {
		return $this->_getAttribute('accent-height');
	}

	public function setAlphabetic($abc) {
		return $this->_setAttribute(
			'alphabetic',
			$this->_normalizeLength($abc)
		);
	}

	public function getAlphabetic() {
		return $this->_getAttribute('alphabetic');
	}

	public function setAscent($ascent) {
		return $this->_setAttribute(
			'ascent',
			$this->_normalizeLength($ascent)
		);
	}

	public function getAscent() {
		return $this->_getAttribute('ascent');
	}

	public function setBBox($bbox) {
		return $this->_setAttribute(
			'bbox',
			$this->_normalizeIdentifier($bbox)
		);
	}

	public function getBBox() {
		return $this->_getAttribute('bbox');
	}

	public function setCapHeight($height) {
		return $this->_setAttribute(
			'cap-height',
			$this->_normalizeLength($height)
		);
	}

	public function getCapHeight() {
		return $this->_getAttribute('cap-height');
	}

	public function setDescent($descent) {
		return $this->_setAttribute(
			'descent',
			$this->_normalizeLength($descent)
		);
	}

	public function getDescent() {
		return $this->_getAttribute('descent');
	}

	public function setHanging($hanging) {
		return $this->_setAttribute(
			'hanging',
			$this->_normalizeLength($hanging)
		);
	}

	public function getHanging() {
		return $this->_getAttribute('hanging');
	}

	public function setIdeographic($ideographic) {
		return $this->_setAttribute(
			'ideographic',
			$this->_normalizeLength($ideographic)
		);
	}

	public function getIdeographic() {
		return $this->_getAttribute('ideographic');
	}

	public function setMathematical($math) {
		return $this->_setAttribute(
			'mathematical',
			$this->_normalizeLength($math)
		);
	}

	public function getMathematical() {
		return $this->_getAttribute('mathematical');
	}

	public function setOverlinePosition($position) {
		return $this->_setAttribute(
			'overline-position',
			$this->_normalizeLength($position)
		);
	}

	public function getOverlinePosition() {
		return $this->_getAttribute('overline-position');
	}

	public function setOverlineThickness($thickness) {
		return $this->_setAttribute(
			'overline-thickness',
			$this->_normalizeLength($thickness)
		);
	}

	public function getOverlineThickness() {
		return $this->_getAttribute('overline-thickness');
	}

	public function setPanose1($panose) {
		return $this->_setAttribute(
			'panose-1',
			$this->_normalizeIdentifier($panose)
		);
	}

	public function getPanose1() {
		return $this->_getAttribute('panose-1');
	}

	public function setSlope($slope) {
		return $this->_setAttribute(
			'slope',
			$this->_normalizeAngle()
		);
	}

	public function getSlope() {
		return $this->_getAttribute('slope');
	}

	public function setHorizontalStem($stem) {
		return $this->_setAttribute(
			'stemh',
			$this->_normalizeLength($stem)
		);
	}

	public function getHorizontalStem() {
		return $this->_getAttribute('stemh');
	}

	public function setVerticalStem($stem) {
		return $this->_setAttribute(
			'stemv',
			$this->_normalizeLength($stem)
		);
	}

	public function getVerticalStem() {
		return $this->_getAttribute('stemv');
	}

	public function setStrikethroughPosition($position) {
		return $this->_setAttribute(
			'strikethrough-position',
			$this->_normalizeLength($position)
		);
	}

	public function getStrikethroughPosition() {
		return $this->_getAttribute('strikethrough-position');
	}

	public function setStrikethroughThickness($thickness) {
		return $this->_setAttribute(
			'strikethrough-thickness',
			$this->_normalizeLength($thickness)
		);
	}

	public function getStrikethroughThickness() {
		return $this->_getAttribute('strikethrough-thickness');
	}

	public function setUnderlinePosition($position) {
		return $this->_setAttribute(
			'underline-position',
			$this->_normalizeLength($position)
		);	
	}

	public function getUnderlinePosition() {
		return $this->_getAttribute('underline-position');
	}

	public function setUnderlineThickness($thickness) {
		return $this->_setAttribute(
			'underline-thickness',
			$this->_normalizeLength($thickness)
		);
	}

	public function getUnderlineThickness() {
		return $this->_getAttribute('underline-thickness');
	}

	public function setUnicodeRange($range) {
		return $this->_setAttribute(
			'unicode-range',
			$this->_normalizeIdentifier($range)
		);
	}

	public function getUnicodeRange() {
		return $this->_getAttribute('unicode-range');
	}

	public function setUnitsPerEm($units) {
		return $this->_setAttribute(
			'units-per-em',
			$this->_normalizeLength($units)
		);
	}

	public function getUnitsPerEm() {
		return $this->_getAttribute('units-per-em');
	}

	public function setVerticalAlphabetic($abc) {
		return $this->_setAttribute(
			'v-alphabetic',
			$this->_normalizeLength($abc)
		);
	}

	public function getVerticalAlphabetic() {
		return $this->_getAttribute('v-alphabetic');
	}

	public function setVerticalHanging($hanging) {
		return $this->_setAttribute(
			'v-hanging',
			$this->_normalizeLength($hanging)
		);
	}

	public function getVerticalHanging() {
		return $this->_getAttribute('v-hanging');
	}

	public function setVerticalIdeographic($ideographic) {
		return $this->_setAttribute(
			'v-ideographic',
			$this->_normalizeLength($ideographic)
		);
	}

	public function getVerticalIdeographic() {
		return $this->_getAttribute('v-ideographic');
	}

	public function setVerticalMathematical($math) {
		return $this->_setAttribute(
			'v-mathematical',
			$this->_normalizeLength($math)
		);
	}

	public function getVerticalMathematical() {
		return $this->_getAttribute('v-mathematical');
	}

	public function setWidths($widths) {
		return $this->_setAttribute(
			'widths',
			$this->_normalizeIdentifier($widths)
		);
	}

	public function getWidths() {
		return $this->_getAttribute('widths');
	}

	public function setXHeight($height) {
		return $this->_setAttribute(
			'x-height',
			$this->_normalizeLength($height)
		);
	}

	public function getXHeight() {
		return $this->_getAttribute('x-height');
	}

	public function setSources(array $sources) {
		$this->_sources = array();
		return $this->addSources($sources);
	}

	public function addSources(array $sources) {
		foreach($sources as $source) {
			if(!$source instanceof IFontFaceSource) {
				throw new InvalidArgumentException(
					'Invalid font face source detected'
				);
			}

			$this->addSource($source);
		}

		return $this;
	}

	public function addSource(IFontFaceSource $source) {
		$this->_sources[] = $source;
		return $this;
	}

	public function getSources() {
		return $this->_sources;
	}

	public function removeSource(IFontFaceSource $source) {
		foreach($this->_sources as $i => $test) {
			if($test === $source) {
				unset($this->_sources[$i]);
				break;
			}
		}

		return $this;
	}

	public function clearSources() {
		unset($this->_sources[$i]);
		return $this;
	}

// Dump
	public function getDumpProperties() {
		$output = $this->_attributes;

		if(!empty($this->_sources)) {
			$output['sources'] = $this->_sources;
		}

		return $output;
	}
}


// Face source
class Font_FaceSource implements IFontFaceSource, core\IDumpable {

	use TCustomContainerElement;
	use TAttributeModule;
	use TAttributeModule_Core;

	protected $_uri;
	protected $_name;

	public function __construct($uri=null, $name=null) {
		if($uri instanceof IFontFaceUri) {
			$this->setUriElement($uri);
		} else if($uri !== null) {
			$this->setUri($uri);
		}

		if($name instanceof IFontName) {
			$this->setNameElement($name);
		} else if($name !== null) {
			$this->setName($name);
		}
	}

	public function setUri($uri) {
		if($uri !== null) {
			$uri = (new Font_FaceUri())->setLinkHref($uri);
		}

		return $this->uriElement($uri);
	}

	public function setUriElement(IFontFaceUri $uri) {
		$this->_uri = $uri;
		return $this;
	}

	public function getUri() {
		return $this->_uri;
	}

	public function setName($name) {
		if($name !== null) {
			$name = (new Font_FaceName())->setName($name);
		}

		return $this->nameElement($name);
	}

	public function setNameElement(IFontFaceName $name) {
		$this->_name = $name;
		return $this;
	}

	public function getName() {
		return $this->_name;
	}

// Dump
	public function getDumpProperties() {
		$output = $this->_attributes;

		if($this->_uri) {
			$output['uri'] = $this->_uri;
		}

		if($this->_name) {
			$output['name'] = $this->_name;
		}

		return $output;
	}
}



// Uri
class Font_FaceUri implements IFontFaceUri, core\IDumpable {

	use TCustomContainerElement;
	use TAttributeModule;
	use TAttributeModule_Core;
	use TAttributeModule_XLink;

	protected $_format;

	public function __construct($format=null) {
		if($format instanceof IFontFaceFormat) {
			$this->setFormatElement($format);
		} else if($format !== null) {
			$this->setFormat($format);
		}
	}

	public function setFormat($string) {
		if($string !== null) {
			$string = (new Font_FaceFormat())->setString($string);
		}

		return $this->setFormatElement($string);
	}

	public function setFormatElement(IFontFaceFormat $format=null) {
		$this->_format = $format;
		return $this;
	}

	public function getFormat() {
		return $this->_format;
	}

// Dump
	public function getDumpProperties() {
		$output = $this->_attributes;

		if($this->_format) {
			$output['format'] = $this->_format;
		}

		return $output;
	}
}




// Format
class Font_FaceFormat implements IFontFaceFormat, core\IDumpable {

	use TCustomContainerElement;
	use TAttributeModule;
	use TAttributeModule_Core;

	public function __construct($string=null) {
		if($string !== null) {
			$this->setString($string);
		}
	}

	public function setString($string) {
		return $this->_setAttribute(
			'string',
			$this->_normalizeText($string)
		);
	}

	public function getString() {
		return $this->_getAttribute('string');
	}

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}



// Name
class Font_FaceName implements IFontFaceName, core\IDumpable {

	use TCustomContainerElement;
	use TAttributeModule;
	use TAttributeModule_Core;

	public function __construct($name=null) {
		$this->setName($name);
	}

	public function setName($name) {
		return $this->_setAttribute(
			'name',
			$this->_normalizeIdentifier($name)
		);
	}

	public function getName() {
		return $this->_getAttribute('name');
	}

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}




// Glyph
class Font_Glyph implements IFontGlyph, core\IDumpable {

	use TCustomContainerElement;
	use TAttributeModule;
	use TAttributeModule_Clip;
	use TAttributeModule_Container;
	use TAttributeModule_Core;
	use TAttributeModule_Cursor;
	use TAttributeModule_Filter;
	use TAttributeModule_FilterColor;
	use TAttributeModule_Flood;
	use TAttributeModule_Font;
	use TAttributeModule_FontAdvance;
	use TAttributeModule_FontVerticalOrigin;
	use TAttributeModule_Gradient;
	use TAttributeModule_Graphics;
	use TAttributeModule_Marker;
	use TAttributeModule_Mask;
	use TAttributeModule_PathData;
	use TAttributeModule_Paint;
	use TAttributeModule_PaintOpacity;
	use TAttributeModule_Style;
	use TAttributeModule_Text;
	use TAttributeModule_TextContent;
	use TAttributeModule_Viewport;

	public function setArabicForm($form) {
		return $this->_setAttribute(
			'arabic-form',
			$this->_normalizeKeyword(
				$form,
				['initial', 'medial', 'terminal', 'isolated'],
				'arabic-form'
			)	
		);
	}

	public function getArabicForm() {
		return $this->_getAttribute('arabic-form');
	}

	public function setGlyphName($name) {
		return $this->_setAttribute(
			'glyph-name',
			$this->_normalizeIdentifier($name)
		);
	}

	public function getGlyphName() {
		return $this->_getAttribute('glyph-name');
	}

	public function setLanguage($language) {
		return $this->_setAttribute(
			'lang',
			$this->_normalizeIdentifier($language)
		);
	}

	public function getLanguage() {
		return $this->_getAttribute('lang');
	}

	public function setOrientation($orientation) {
		return $this->_setAttribute(
			'orientation',
			$this->_normalizeKeyword(
				$orientation,
				['h', 'v'],
				'orientation'
			)
		);
	}

	public function getOrientation() {
		return $this->_getAttribute('orientation');
	}

	public function setUnicode($unicode) {
		return $this->_setAttribute(
			'unicode',
			$this->_normalizeIdentifier($unicode)
		);
	}

	public function getUnicode() {
		return $this->_getAttribute('unicode');
	}

// Dump
	public function getDumpProperties() {
		return $this->_attributes;
	}
}