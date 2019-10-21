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

trait TAttributeModule
{
    protected $_attributes = [];

    public function getName()
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getElementName()
    {
        return strtolower($this->getName());
    }

    public function prepareAttributes(IDocument $document)
    {
        if (isset($this->_position)) {
            $this->_position->convertRelativeAnchors($document->getWidth(), $document->getHeight());
        }

        if (isset($this->_points)) {
            foreach ($this->_points as $point) {
                $point->convertRelativeAnchors($document->getWidth(), $document->getHeight());
            }

            $this->_onSetPoints();
        }

        $output = $this->_attributes;

        if (null !== ($extra = $this->_getExtraAttributes())) {
            $output = array_merge($output, $extra);
        }

        foreach ($this->_attributes as $key => $value) {
            $output[$key] = (string)$value;
        }

        return $output;
    }

    protected function _onSetPoints()
    {
    }

    protected function _getExtraAttributes()
    {
        return null;
    }

    protected static function _extractInputAttribute(array $attributes, $name, $default=null)
    {
        if (isset($attributes[$name])) {
            $output = $attributes[$name];
            unset($attributes[$name]);
            return $output;
        } else {
            return $default;
        }
    }

    public function applyInputAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            switch ($key) {
                case 'r': $key = 'radius'; break;
                case 'rx': $key = 'xRadius'; break;
                case 'ry': $key = 'yRadius'; break;
                case 'cx': $key = 'xPosition'; break;
                case 'cy': $key = 'yPosition'; break;
            }

            $func = 'set'.str_replace(' ', '', ucwords(str_replace('-', ' ', $key)));

            if (method_exists($this, $func)) {
                $this->{$func}($value);
            } else {
                $this->_attributes[$key] = $value;
            }
        }

        return $this;
    }

    protected static function _xmlToObject(flex\xml\IReadable $reader, IElement $parent=null)
    {
        $output = null;
        $return = true;
        $attributes = $reader->getAttributes();
        $tagName = $reader->getTagName();

        switch ($tagName) {

            // Structure
            case 'svg':
                $output = new Document();
                break;

            case 'g':
                $output = new Group();
                break;

            // Shape
            case 'circle':
            case 'ellipse':
            case 'image':
            case 'line':
            case 'path':
            case 'polygon':
            case 'polyline':
            case 'rect':
                $output = Shape::fromAttributes($tagName, $attributes);
                break;


            // Description
            case 'title':
                if ($parent instanceof IDescriptionProvider) {
                    $parent->setTitle($reader->getComposedTextContent());
                }

                break;

            case 'desc':
                if ($parent instanceof IDescriptionProvider) {
                    $parent->setDescription($reader->getComposedTextContent());
                }

                break;


            // MetaData
            case 'metadata':
                if ($parent instanceof IMetaDataProvider) {
                    $parent->setMetaData($reader->getInnerXml());
                }

                break;


            // Definitions
            case 'defs':
                $output = new Definitions();
                break;


            // Font
            case 'font':
                $output = new Font();
                break;

            case 'font-face':
                $output = new Font_Face();

                if ($parent instanceof IFontFaceContainer) {
                    $parent->setFontFace($output);
                    $return = false;
                }

                break;

            case 'font-face-format':
                $output = new Font_FaceFormat();

                if ($parent instanceof IFontFaceUri) {
                    $parent->setFormatElement($output);
                    $return = false;
                }

                break;

            case 'font-face-name':
                $output = new Font_FaceName();

                if ($parent instanceof IFontFaceSource) {
                    $parent->setNameElement($output);
                    $return = false;
                }

                break;

            case 'font-face-src':
                $output = new Font_FaceSource();

                if ($parent instanceof IFontFace) {
                    $parent->addSource($output);
                    $return = false;
                }

                break;

            case 'font-face-uri':
                $output = new Font_FaceUri();

                if ($parent instanceof IFontFaceSource) {
                    $parent->setUriElement($output);
                    $return = false;
                }

                break;

            case 'glyph':
                $output = new Font_Glyph();

                if ($parent instanceof IFont) {
                    $parent->addGlyph($output);
                    $return = false;
                }

                break;

            case 'missing-glyph':
                $output = new Font_Glyph();

                if ($parent instanceof IFont) {
                    $parent->setMissingGlyph($output);
                    $return = false;
                }

                break;


            // Filter
            case 'feBlend':
            case 'feColorMatrix':
            case 'feComponentTransfer':
            case 'feComposite':
            case 'feConvolveMatrix':
            case 'feDiffuseLighting':
            case 'feDisplacementMap':
            case 'feDistantLight':
            case 'feFlood':
            case 'feFuncA':
            case 'feFuncB':
            case 'feFuncG':
            case 'feFuncR':
            case 'feGaussianBlur':
            case 'feImage':
            case 'feMerge':
            case 'feMergeNode':
            case 'feMorphology':
            case 'feOffset':
            case 'fePointLight':
            case 'feSpecularLighting':
            case 'feSpotLight':
            case 'feTile':
            case 'feTurbulence':


            case 'a':
            case 'altGlyph':
            case 'altGlyphDef':
            case 'altGlyphItem':
            case 'animate':
            case 'animateColor':
            case 'animateMotion':
            case 'animateTransform':
            case 'clipPath':
            case 'color-profile':
            case 'cursor':
            case 'filter':
            case 'foreignObject':
            case 'glyphRef':
            case 'hkern':
            case 'linearGradient':
            case 'marker':
            case 'mask':
            case 'mpath':
            case 'pattern':
            case 'radialGradient':
            case 'script':
            case 'set':
            case 'stop':
            case 'style':
            case 'switch':
            case 'symbol':
            case 'text':
            case 'textPath':
            case 'tref':
            case 'tspan':
            case 'use':
            case 'view':
            case 'vkern':
            default:
                throw new RuntimeException(
                    'Cannot parse SVG XML - don\'t know what to do with a '.$tagName.' element yet!'
                );
        }

        if ($output) {
            $output->applyInputAttributes($attributes);
            $output->readXml($reader);
        }

        if ($return) {
            return $output;
        }
    }


    public function getGraphicalAttributes()
    {
        $attr = [
            'alignment-baseline', 'baseline-shift', 'clip', 'clip-path', 'clip-rule', 'color', 'color-interpolation',
            'color-interpolation-filters', 'color-profile', 'color-rendering', 'cursor', 'direction', 'display',
            'dominant-baseline', 'enable-background', 'externalResourcesRequired', 'fill', 'fill-opacity', 'fill-rule',
            'filter', 'flood-color', 'flood-opacity', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch',
            'font-style', 'font-variant', 'font-weight', 'glyph-orientation-horizontal', 'glyph-orientation-vertical',
            'image-rendering', 'kerning', 'letter-spacing', 'lighting-color', 'marker-end', 'marker-mid', 'marker-start',
            'mask', 'onactivate', 'onclick', 'onfocusin', 'onfocusout', 'onload', 'onmousedown', 'onmousemove',
            'onmouseout', 'onmouseover', 'onmouseup', 'opacity', 'overflow', 'pointer-events', 'requiredExtensions',
            'requiredFeatures', 'shape-rendering', 'stop-color', 'stop-opacity', 'stroke', 'stroke-dasharray',
            'stroke-dashoffset', 'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke-width',
            'style', 'systemLanguage', 'text-anchor', 'text-decoration', 'text-rendering', 'unicode-bidi', 'visibility',
            'word-spacing', 'writing-mode'
        ];

        $output = [];

        foreach ($this->_attributes as $key => $value) {
            if (in_array($key, $attr)) {
                if (is_object($value)) {
                    $value = clone $value;
                }

                $output[$key] = $value;
            }
        }

        return $output;
    }

    protected function _setAttribute($name, $value)
    {
        if ($value === null) {
            unset($this->_attributes[$name]);
        } else {
            $this->_attributes[$name] = $value;
        }

        return $this;
    }

    protected function _getAttribute($name, $default=null)
    {
        if (isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        }

        return $default;
    }

    protected function _normalizeUnicode($value)
    {
        if (empty($value)) {
            return null;
        }

        return str_replace('&amp;#', '&#', $value);
    }

    protected function _normalizeKeyword($value, array $keywords, $attributeName)
    {
        if (empty($value)) {
            return null;
        }

        $value = strtolower($value);

        foreach ($keywords as $keyword) {
            if (strtolower($keyword) == $value) {
                return $keyword;
            }
        }

        throw new InvalidArgumentException(
            $value.' is not a valid '.$attributeName.' attribute value'
        );
    }

    protected function _normalizeKeywordOrLength($value, array $keywords)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof core\unit\IDisplaySize) {
            return $value;
        }

        $value = strtolower($value);

        foreach ($keywords as $keyword) {
            if (strtolower($keyword) == $value) {
                return $keyword;
            }
        }

        return core\unit\DisplaySize::factory($value, null, true);
    }

    protected function _normalizeLength($value)
    {
        if (empty($value)) {
            return null;
        }

        return core\unit\DisplaySize::factory($value, null, true);
    }

    protected function _normalizeKeywordOrAngle($value, array $keywords)
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof core\unit\IAngle) {
            return $value;
        }

        $value = strtolower($value);

        foreach ($keywords as $keyword) {
            if (strtolower($keyword) == $value) {
                return $keyword;
            }
        }

        return core\unit\Angle::factory($value);
    }

    protected function _normalizeAngle($value)
    {
        if (empty($value)) {
            return null;
        }

        return core\unit\Angle::factory($value, null, true);
    }

    protected function _normalizeKeywordOrIdentifier($value, array $keywords)
    {
        if (empty($value)) {
            return null;
        }

        $orig = $value;
        $value = strtolower($value);

        foreach ($keywords as $keyword) {
            if (strtolower($keyword) == $value) {
                return $keyword;
            }
        }

        return (string)$orig;
    }

    protected function _normalizeKeywordOrNumber($value, array $keywords, $attributeName)
    {
        if (empty($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        $value = strtolower($value);

        foreach ($keywords as $keyword) {
            if (strtolower($keyword) == $value) {
                return $keyword;
            }
        }

        throw new InvalidArgumentException(
            $value.' is not a valid '.$attributeName.' attribute value'
        );
    }

    protected function _normalizeInheritedColor($color)
    {
        if (empty($color)) {
            return null;
        }

        $orig = $color;
        $color = strtolower($color);

        if ($color == 'none') {
            return $color;
        }

        if ($color == 'currentcolor') {
            return 'currentColor';
        }

        try {
            return neon\Color::factory($color);
        } catch (neon\EColor $e) {
        }

        return (string)$orig;
    }

    protected function _normalizeIdentifier($iri)
    {
        if (empty($iri)) {
            $iri = null;
        } else {
            $iri = (string)$iri;
        }

        return $iri;
    }

    protected function _normalizeScript($script)
    {
        if (empty($script)) {
            return null;
        }

        return (string)$script;
    }

    protected function _normalizeText($text)
    {
        if (empty($text)) {
            return null;
        }

        return (string)$text;
    }

    protected function _normalizeBoolean($boolean)
    {
        if (empty($boolean)) {
            return null;
        }

        if (is_string($boolean)) {
            $boolean = flex\Text::stringToBoolean($boolean);
        }

        return (bool)$boolean;
    }

    protected function _normalizeOpacity($opacity)
    {
        if (empty($opacity)) {
            return null;
        }

        $opacity = (float)$opacity;

        if ($opacity < 0) {
            $opacity = 0.0;
        } elseif ($opacity > 1) {
            $opacity = 1.0;
        }

        return $opacity;
    }
}


// Animation events
trait TAttributeModule_AnimationEvents
{
    public function setOnBeginScript($script)
    {
        return $this->_setAttribute(
            'onbegin',
            $this->_normalizeScript($script)
        );
    }

    public function getOnBeginScript()
    {
        return $this->_getAttribute('onbegin');
    }

    public function setOnEndScript($script)
    {
        return $this->_setAttribute(
            'onend',
            $this->_normalizeScript($script)
        );
    }

    public function getOnEndScript()
    {
        return $this->_getAttribute('onend');
    }

    public function setOnRepeatScript($script)
    {
        return $this->_setAttribute(
            'onrepeat',
            $this->_normalizeScript($script)
        );
    }

    public function getOnRepeatScript()
    {
        return $this->_getAttribute('onrepeat');
    }

    public function setOnLoadScript($script)
    {
        return $this->_setAttribute(
            'onload',
            $this->_normalizeScript($script)
        );
    }

    public function getOnLoadScript()
    {
        return $this->_ongetAttribute('onload');
    }
}



// Aspect ratio
trait TAttributeModule_AspectRatio
{
    public function setPreserveAspectRatio($preserve)
    {
        return $this->_setAttribute(
            'preserveAspectRatio',
            $this->_normalizeBoolean($preserve)
        );
    }

    public function getPreserveAspectRatio()
    {
        return $this->_getAttribute('preserveAspectRatio');
    }
}



// Base profile
trait TAttributeModule_BaseProfile
{
    public function setBaseProfile($profile)
    {
        return $this->_setAttribute(
            'baseProfile',
            $this->_normalizeIdentifier($profile)
        );
    }

    public function getBaseProfile()
    {
        return $this->_getAttribute('baseProfile');
    }
}




// Basic graphics
trait TAttributeModule_BasicGraphics
{
    public function setDisplay($display)
    {
        return $this->_setAttribute(
            'display',
            $this->_normalizeKeyword(
                $display,
                [
                    'block', 'compact', 'inherit', 'inline', 'inline-table', 'list-item',
                    'marker', 'none', 'run-in', 'table', 'table-caption', 'table-cell', 'table-column',
                    'table-column-group', 'table-footer-group', 'table-header-group', 'table-row',
                    'table-row-group'
                ],
                'display'
            )
        );
    }

    public function getDisplay()
    {
        return $this->_getAttribute('display');
    }

    public function setVisibility($visibility)
    {
        return $this->_setAttribute(
            'visibility',
            $this->_normalizeKeyword(
                $visibility,
                ['collapse', 'hidden', 'inherit', 'visible'],
                'visibility'
            )
        );
    }

    public function getVisibility()
    {
        return $this->_getAttribute('visibility');
    }
}


// Basic paint
trait TAttributeModule_BasicPaint
{
    public function setColor($color)
    {
        return $this->_setAttribute(
            'color',
            empty($color) ?
                null :
                neon\Color::factory($color)
        );
    }

    public function getColor()
    {
        return $this->_getAttribute('color');
    }

    public function setFill($fill)
    {
        return $this->_setAttribute(
            'fill',
            $this->_normalizeInheritedColor($fill)
        );
    }

    public function getFill()
    {
        return $this->_getAttribute('fill');
    }

    public function setFillRule($rule)
    {
        return $this->_setAttribute(
            'full-rule',
            $this->_normalizeKeyword(
                $rule,
                ['evenodd', 'inherit', 'nonzero'],
                'fill-rule'
            )
        );

        return $this;
    }

    public function getFillRule()
    {
        return $this->_getAttribute('fill-rule');
    }

    public function setStroke($stroke)
    {
        return $this->_setAttribute(
            'stroke',
            $this->_normalizeInheritedColor($stroke)
        );
    }

    public function getStroke()
    {
        return $this->_getAttribute('stroke');
    }

    public function setStrokeDashArray($dash)
    {
        if (empty($dash)) {
            return $this->_setAttribute('stroke-dasharray', null);
        }

        if (is_string($dash)) {
            $dash = explode(',', $dash);
        }

        if (!is_array($dash)) {
            $dash = [$dash];
        }

        foreach ($dash as $i => $part) {
            $dash[$i] = core\unit\DisplaySize::factory(trim($part), null, true);
        }

        return $this->_setAttribute('strokeDashArray', $dash);
    }

    public function getStrokeDashArray()
    {
        return $this->_getAttribute('stroke-dasharray');
    }

    public function setStrokeDashOffset($offset)
    {
        return $this->_setAttribute(
            'stroke-dashoffset',
            $this->_normalizeKeywordOrLength(
                $offset,
                ['inherit']
            )
        );
    }

    public function getStrokeDashOffset()
    {
        return $this->_getAttribute('stroke-dashoffset');
    }

    public function setStrokeLineCap($lineCap)
    {
        return $this->_setAttribute(
            'stroke-linecap',
            $this->_normalizeKeyword(
                $lineCap,
                ['butt', 'inherit', 'round', 'square'],
                'stroke-linecap'
            )
        );
    }

    public function getStrokeLineCap()
    {
        return $this->_getAttribute('stroke-linecap');
    }

    public function setStrokeLineJoin($lineJoin)
    {
        return $this->_setAttribute(
            'stroke-linejoin',
            $this->_normalizeKeyword(
                $lineJoin,
                ['bevel', 'inherit', 'miter', 'round'],
                'stroke-linejoin'
            )
        );
    }

    public function getStrokeLineJoin()
    {
        return $this->_getAttribute('stroke-linejoin');
    }

    public function setStrokeMiterLimit($limit)
    {
        if (empty($limit)) {
            return $this->_setAttribute('stroke-miterlimit', null);
        }

        $limit = (int)$limit;

        if ($limit < 1) {
            throw new InvalidArgumentException(
                'Stroke miter limit must be 1 or higher'
            );
        }

        return $this->_setAttribute('strokeMiterLimit', $limit);
    }

    public function getStrokeMiterLimit()
    {
        return $this->_getAttribute('stroke-miterlimit');
    }

    public function setStrokeWidth($width)
    {
        return $this->_setAttribute(
            'stroke-width',
            $this->_normalizeKeywordOrLength(
                $width,
                ['inherit']
            )
        );
    }

    public function getStrokeWidth()
    {
        return $this->_getAttribute('stroke-width');
    }

    public function setColorRendering($rendering)
    {
        return $this->_setAttribute(
            'color-rendering',
            $this->_normalizeKeyword(
                $rendering,
                ['auto', 'inherit', 'optimizeQuality', 'optimizeSpeed'],
                'color-rendering'
            )
        );
    }

    public function getColorRendering()
    {
        return $this->_getAttribute('color-rendering');
    }
}


// Clip
trait TAttributeModule_Clip
{
    public function setClipPath($path)
    {
        return $this->_setAttribute(
            'clip-path',
            $this->_normalizeKeywordOrIdentifier(
                $path,
                ['inherit', 'none']
            )
        );
    }

    public function getClipPath()
    {
        return $this->_getAttribute('clip-path');
    }

    public function setClipRule($rule)
    {
        return $this->_setAttribute(
            'clipRule',
            $this->_normalizeKeyword(
                $rule,
                ['evenodd', 'inherit', 'nonzero'],
                'clip-rule'
            )
        );
    }

    public function getClipRule()
    {
        return $this->_getAttribute('clip-rule');
    }
}




// Conditional
trait TAttributeModule_Conditional
{
    public function setRequiredFeatures($features)
    {
        return $this->_setAttribute(
            'requiredFeatures',
            $this->_normalizeIdentifier($features)
        );
    }

    public function getRequiredFeatures()
    {
        return $this->_getAttribute('requiredFeatures');
    }

    public function setRequiredExtensions($extensions)
    {
        return $this->_setAttribute(
            'requiredExtensions',
            $this->_normalizeIdentifier($extensions)
        );
    }

    public function getRequiredExtensions()
    {
        return $this->_getAttribute('requiredExtensions');
    }

    public function setSystemLanguage($lang)
    {
        return $this->_setAttribute(
            'systemLanguage',
            $this->_normalizeIdentifier($lang)
        );
    }

    public function getSystemLanguage()
    {
        return $this->_getAttribute('systemLanguage');
    }
}





// Container
trait TAttributeModule_Container
{
    public function setEnableBackground($background)
    {
        if (empty($background)) {
            return $this->_setAttribute('enable-background', null);
        }

        if ($background == 'accumulate' || $background == 'inherit' || substr($background, 0, 4) == 'new ') {
            return $this->_setAttribute('enableBackground', $background);
        }

        throw new InvalidArgumentException(
            $background.' is not a valid enable-background attribute value'
        );
    }

    public function getEnableBackground()
    {
        return $this->_getAttribute('enable-background');
    }
}


// Core
trait TAttributeModule_Core
{
    public function setId(?string $id)
    {
        return $this->_setAttribute(
            'id',
            $this->_normalizeIdentifier($id)
        );
    }

    public function getId(): ?string
    {
        return $this->_getAttribute('id');
    }

    public function setXmlBase($baseIri)
    {
        return $this->_setAttribute(
            'xml:base',
            $this->_normalizeIdentifier($baseIri)
        );
    }

    public function getXmlBase()
    {
        return $this->_getAttribute('xml:base');
    }

    public function setXmlLang($lang)
    {
        return $this->_setAttribute(
            'xml:lang',
            $this->_normalizeIdentifier($lang)
        );
    }

    public function getXmlLang()
    {
        return $this->_getAttribute('xml:lang');
    }

    public function setXmlSpace($space)
    {
        return $this->_setAttribute(
            'xml:space',
            $this->_normalizeKeyword(
                $space,
                ['preserve', 'default'],
                'xml:space'
            )
        );
    }

    public function getXmlSpace()
    {
        return $this->_getAttribute('xml:space');
    }
}



// Cursor
trait TAttributeModule_Cursor
{
    public function setCursor($cursor)
    {
        return $this->_setAttribute(
            'cursor',
            $this->_normalizeKeywordOrIdentifier(
                $cursor,
                [
                    'auto', 'crosshair', 'default', 'e-resize', 'help', 'inherit', 'move',
                    'n-resize', 'ne-resize', 'nw-resize', 'pointer', 's-resize', 'se-resize',
                    'sw-resize', 'text', 'w-resize', 'wait'
                ]
            )
        );
    }

    public function getCursor()
    {
        return $this->_getAttribute('cursor');
    }
}



// Dimensions
trait TAttributeModule_Dimension
{
    public function setDimensions($width, $height)
    {
        return $this->setWidth($width)->setHeight($height);
    }

    public function setWidth($width)
    {
        return $this->_setAttribute(
            'width',
            core\unit\DisplaySize::factory($width, null, true)
        );
    }

    public function getWidth()
    {
        return $this->_getAttribute('width');
    }

    public function setHeight($height)
    {
        return $this->_setAttribute(
            'height',
            core\unit\DisplaySize::factory($height, null, true)
        );
    }

    public function getHeight()
    {
        return $this->_getAttribute('height');
    }
}




// Document events
trait TAttributeModule_DocumentEvents
{
    public function setOnUnloadScript($script)
    {
        return $this->_setAttribute(
            'onunload',
            $this->_normalizeScript($script)
        );
    }

    public function getOnUnloadScript()
    {
        return $this->_getAttribute('onunload');
    }

    public function setOnAbortScript($script)
    {
        return $this->_setAttribute(
            'onabort',
            $this->_normalizeScript($script)
        );
    }

    public function getOnAbortScript()
    {
        return $this->_getAttribute('onabort');
    }

    public function setOnErrorScript($script)
    {
        return $this->_setAttribute(
            'onerror',
            $this->_normalizeScript($script)
        );
    }

    public function getOnErrorScript()
    {
        return $this->_getAttribute('onerror');
    }

    public function setOnResizeScript($script)
    {
        return $this->_setAttribute(
            'onresize',
            $this->_normalizeScript($script)
        );
    }

    public function getOnResizeScript()
    {
        return $this->_getAttribute('onresize');
    }

    public function setOnScrollScript($script)
    {
        return $this->_setAttribute(
            'onscroll',
            $this->_normalizeScript($script)
        );
    }

    public function getOnScrollScript()
    {
        return $this->_getAttribute('onscroll');
    }

    public function setOnZoomScript($script)
    {
        return $this->_setAttribute(
            'onzoom',
            $this->_normalizeScript($script)
        );
    }

    public function getOnZoomScript()
    {
        return $this->_getAttribute('onzoom');
    }
}


// External resources
trait TAttributeModule_ExternalResources
{
    public function setExternalResourcesRequired($required)
    {
        return $this->_setAttribute(
            'externalResourcesRequired',
            $this->_normalizeBoolean($required)
        );
    }

    public function getExternalResourcesRequired()
    {
        return $this->_getAttribute('externalResourcesRequired');
    }
}



// Filter
trait TAttributeModule_Filter
{
    public function setFilter($filter)
    {
        return $this->_setAttribute(
            'filter',
            $this->_normalizeKeywordOrIdentifier(
                $filter,
                ['inherit', 'none']
            )
        );
    }

    public function getFilter()
    {
        return $this->_getAttribute('filter');
    }
}


// Filter color
trait TAttributeModule_FilterColor
{
    public function setColorInterpolationFilters($interpolation)
    {
        return $this->_setAttribute(
            'color-interpolation-filters',
            $this->_normalizeKeyword(
                $interpolation,
                ['auto', 'inherit', 'linearRGB', 'sRGB'],
                'color-interpolation-filters'
            )
        );
    }

    public function getColorInterpolationFilters()
    {
        return $this->_getAttribute('color-interpolation-filters');
    }

    public function setLightingColor($color)
    {
        return $this->_setAttribute(
            'lighting-color',
            $this->_normalizeInheritedColor($color)
        );
    }

    public function getLightingColor()
    {
        return $this->_getAttribute('lighting-color');
    }
}




// Flood
trait TAttributeModule_Flood
{
    public function setFloodColor($color)
    {
        return $this->_setAttribute(
            'flood-color',
            $this->_normalizeInheritedColor($color)
        );
    }

    public function getFloodColor()
    {
        return $this->_getAttribute('flood-color');
    }

    public function setFloodOpacity($opacity)
    {
        return $this->_setAttribute(
            'flood-opacity',
            $this->_normalizeOpacity($opacity)
        );
    }

    public function getFloodOpacity()
    {
        return $this->_getAttribute('flood-opacity');
    }
}





// Font
trait TAttributeModule_Font
{
    public function setFontFamily($family)
    {
        return $this->_setAttribute(
            'font-family',
            $this->_normalizeIdentifier($family)
        );
    }

    public function getFontFamily()
    {
        return $this->_getAttribute('font-family');
    }

    public function setFontSize($size)
    {
        return $this->_setAttribute(
            'font-size',
            $this->_normalizeKeywordOrLength(
                $size,
                ['inherit']
            )
        );
    }

    public function getFontSize()
    {
        return $this->_getAttribute('font-size');
    }

    public function setFontSizeAdjust($adjust)
    {
        return $this->_setAttribute(
            'font-size-adjust',
            $this->_normalizeKeywordOrNumber(
                $adjust,
                ['inherit'],
                'font-size-adjust'
            )
        );
    }

    public function getFontSizeAdjust()
    {
        return $this->_getAttribute('font-size-adjust');
    }

    public function setFontStretch($stretch)
    {
        return $this->_setAttribute(
            'font-stretch',
            $this->_normalizeKeyword(
                $stretch,
                [
                    'condensed', 'expanded', 'extra-condensed', 'extra-expanded', 'inherit', 'narrower',
                    'normal', 'semi-condensed', 'semi-expanded', 'ultra-condensed', 'ultra-expanded', 'wider'
                ],
                'font-stretch'
            )
        );
    }

    public function getFontStretch()
    {
        return $this->_getAttribute('font-stretch');
    }

    public function setFontStyle($style)
    {
        return $this->_setAttribute(
            'font-style',
            $this->_normalizeKeyword(
                $style,
                ['inherit', 'italic', 'normal', 'oblique'],
                'font-style'
            )
        );
    }

    public function getFontStyle()
    {
        return $this->_getAttribute('font-style');
    }

    public function setFontVariant($variant)
    {
        return $this->_setAttribute(
            'font-variant',
            $this->_normalizeKeyword(
                $variant,
                ['inherit', 'normal', 'small-caps'],
                'font-variant'
            )
        );
    }

    public function getFontVariant()
    {
        return $this->_getAttribute('font-variant');
    }

    public function setFontWeight($weight)
    {
        return $this->_setAttribute(
            'font-weight',
            $this->_normalizeKeyword(
                $weight,
                ['100', '200', '300', '400', '500', '600', '700', '800', '900', 'bold', 'bolder', 'inherit', 'lighter', 'normal'],
                'font-weight'
            )
        );
    }

    public function getFontWeight()
    {
        return $this->_getAttribute('font-weight');
    }
}



trait TAttributeModule_FontAdvance
{
    public function setHorizontalAdvance($advance)
    {
        return $this->_setAttribute(
            'horiz-adv-x',
            $this->_normalizeLength($advance)
        );
    }

    public function getHorizontalAdvance()
    {
        return $this->_getAttribute('horiz-adv-x');
    }

    public function getVerticalAdvance()
    {
        return $this->_getAttribute('vert-adv-y');
    }

    public function setVerticalOriginX($x)
    {
        return $this->_setAttribute(
            'vert-origin-x',
            $this->_normalizeLength($x)
        );
    }
}

trait TAttributeModule_FontHorizontalOrigin
{
    public function setHorizontalOriginX($x)
    {
        return $this->_setAttribute(
            'horiz-origin-x',
            $this->_normalizeLength($x)
        );
    }

    public function getHorizontalOriginX()
    {
        return $this->_getAttribute('horiz-origin-x');
    }

    public function setHorizontalOriginY($y)
    {
        return $this->_setAttribute(
            'horiz-origin-y',
            $this->_normalizeLength($y)
        );
    }

    public function getHorizontalOriginY()
    {
        return $this->_getAttribute('horiz-origin-y');
    }
}

trait TAttributeModule_FontVerticalOrigin
{
    public function setVerticalAdvance($advance)
    {
        return $this->_setAttribute(
            'vert-adv-y',
            $this->_normalizeLength($advance)
        );
    }

    public function getVerticalOriginX()
    {
        return $this->_getAttribute('vert-origin-x');
    }

    public function setVerticalOriginY($y)
    {
        return $this->_setAttribute(
            'vert-origin-y',
            $this->_normalizeLength($y)
        );
    }

    public function getVerticalOriginY()
    {
        return $this->_getAttribute('vert-origin-y');
    }
}


trait TAttributeModule_FontDefinition
{
    use TAttributeModule_FontAdvance;
    use TAttributeModule_FontHorizontalOrigin;
    use TAttributeModule_FontVerticalOrigin;
}



// Graphical element events
trait TAttributeModule_GraphicalElementEvents
{
    public function setOnFocusInScript($script)
    {
        return $this->_setAttribute(
            'onfocusin',
            $this->_normalizeScript($script)
        );
    }

    public function getOnFocusInScript()
    {
        return $this->_getAttribute('onfocusin');
    }

    public function setOnFocusOutScript($script)
    {
        return $this->_setAttribute(
            'onfocusout',
            $this->_normalizeScript($script)
        );
    }

    public function getOnFocusOutScript()
    {
        return $this->_getAttribute('onfocusout');
    }

    public function setOnActivateScript($script)
    {
        return $this->_setAttribute(
            'onactivate',
            $this->_normalizeScript($script)
        );
    }

    public function getOnActivateScript()
    {
        return $this->_getAttribute('onactivate');
    }

    public function setOnClickScript($script)
    {
        return $this->_setAttribute(
            'onclick',
            $this->_normalizeScript($script)
        );
    }

    public function getOnClickScript()
    {
        return $this->_getAttribute('onclick');
    }

    public function setOnMouseDownScript($script)
    {
        return $this->_setAttribute(
            'onmousedown',
            $this->_normalizeScript($script)
        );
    }

    public function getOnMouseDownScript()
    {
        return $this->_getAttribute('onmousedown');
    }

    public function setOnMouseUpScript($script)
    {
        return $this->_setAttribute(
            'onmouseup',
            $this->_normalizeScript($script)
        );
    }

    public function getOnMouseUpScript()
    {
        return $this->_getAttribute('onmouseup');
    }

    public function setOnMouseOverScript($script)
    {
        return $this->_setAttribute(
            'onmouseover',
            $this->_normalizeScript($script)
        );
    }

    public function getOnMouseOverScript()
    {
        return $this->_getAttribute('onmouseover');
    }

    public function setOnMouseMoveScript($script)
    {
        return $this->_setAttribute(
            'onmousemove',
            $this->_normalizeScript($script)
        );
    }

    public function getOnMouseMoveScript()
    {
        return $this->_getAttribute('onmousemove');
    }

    public function setOnMouseOutScript($script)
    {
        return $this->_setAttribute(
            'onmouseout',
            $this->_normalizeScript($script)
        );
    }

    public function getOnMouseOutScript()
    {
        return $this->_getAttribute('onmouseout');
    }

    public function setOnLoadScript($script)
    {
        return $this->_setAttribute(
            'onload',
            $this->_normalizeScript($script)
        );
    }

    public function getOnLoadScript()
    {
        return $this->_getAttribute('onload');
    }
}


// Graphics
trait TAttributeModule_Graphics
{
    use TAttributeModule_BasicGraphics;

    public function setImageRendering($rendering)
    {
        return $this->_setAttribute(
            'image-rendering',
            $this->_normalizeKeyword(
                $rendering,
                ['auto', 'inherit', 'optimizeQuality', 'optimizeSpeed'],
                'image-rendering'
            )
        );
    }

    public function getImageRendering()
    {
        return $this->_getAttribute('image-rendering');
    }

    public function setPointerEvents($events)
    {
        return $this->_setAttribute(
            'pointer-events',
            $this->_normalizeKeyword(
                $events,
                ['all', 'fill', 'inherit', 'none', 'painted', 'stroke', 'visible', 'visibleFill', 'visiblePainted', 'visibleStroke'],
                'pointer-events'
            )
        );
    }

    public function getPointerEvents()
    {
        return $this->_getAttribute('pointer-events');
    }

    public function setShapeRendering($rendering)
    {
        return $this->_setAttribute(
            'shape-rendering',
            $this->_normalizeKeyword(
                $rendering,
                ['auto', 'inherit', 'crispEdges', 'geometricPrecision'],
                'shape-rendering'
            )
        );
    }

    public function getShapeRendering()
    {
        return $this->_getAttribute('shape-rendering');
    }

    public function setTextRendering($rendering)
    {
        return $this->_setAttribute(
            'text-rendering',
            $this->_normalizeKeyword(
                $rendering,
                ['auto', 'inherit', 'geometricPrecision', 'optimizeLegibility'],
                'text-rendering'
            )
        );
    }

    public function getTextRendering()
    {
        return $this->_getAttribute('text-rendering');
    }
}



// Gradient
trait TAttributeModule_Gradient
{
    public function setStopColor($color)
    {
        return $this->_setAttribute(
            'stop-color',
            $this->_normalizeInheritedColor($color)
        );
    }

    public function getStopColor()
    {
        return $this->_getAttribute('stop-color');
    }

    public function setStopOpacity($opacity)
    {
        return $this->_setAttribute(
            'stop-opacity',
            $this->_normalizeOpacity($opacity)
        );
    }

    public function getStopOpacity()
    {
        return $this->_getAttribute('stop-opacity');
    }
}




// Marker
trait TAttributeModule_Marker
{
    public function setMarkerStart($start)
    {
        return $this->_setAttribute(
            'marker-start',
            $this->_normalizeKeywordOrIdentifier(
                $start,
                ['inherit']
            )
        );
    }

    public function getMarkerStart()
    {
        return $this->_getAttribute('marker-start');
    }

    public function setMarkerMid($mid)
    {
        return $this->_setAttribute(
            'marker-mid',
            $this->_normalizeKeywordOrIdentifier(
                $mid,
                ['inherit']
            )
        );
    }

    public function getMarkerMid()
    {
        return $this->_getAttribute('marker-mid');
    }

    public function setMarkerEnd($end)
    {
        return $this->_setAttribute(
            'marker-end',
            $this->_normalizeKeywordOrIdentifier(
                $end,
                ['inherit']
            )
        );
    }

    public function getMarkerEnd()
    {
        return $this->_getAttribute('marker-end');
    }
}



// Mask
trait TAttributeModule_Mask
{
    public function setMask($mask)
    {
        return $this->_setAttribute(
            'mask',
            $this->_normalizeKeywordOrIdentifier(
                $mask,
                ['inherit']
            )
        );
    }

    public function getMask()
    {
        return $this->_getAttribute('mask');
    }
}




// Paint
trait TAttributeModule_Paint
{
    use TAttributeModule_BasicPaint;

    public function setColorProfile($profile)
    {
        return $this->_setAttribute(
            'color-profile',
            $this->_normalizeKeywordOrIdentifier(
                $profile,
                ['auto', 'inherit', 'sRGB']
            )
        );
    }

    public function getColorProfile()
    {
        return $this->_getAttribute('color-profile');
    }

    public function setColorInterpolation($interpolation)
    {
        return $this->_setAttribute(
            'color-interpolation',
            $this->_normalizeKeyword(
                $interpolation,
                ['auto', 'inherit', 'linearRGB', 'sRGB'],
                'color-interpolation'
            )
        );
    }

    public function getColorInterpolation()
    {
        return $this->_getAttribute('color-interpolation');
    }
}


// Paint opacity
trait TAttributeModule_PaintOpacity
{
    public function setOpacity($opacity)
    {
        return $this->_setAttribute(
            'opacity',
            $this->_normalizeOpacity($opacity)
        );
    }

    public function getOpacity()
    {
        return $this->_getAttribute('opacity');
    }

    public function setStrokeOpacity($opacity)
    {
        return $this->_setAttribute(
            'stroke-opacity',
            $this->_normalizeOpacity($opacity)
        );
    }

    public function getStrokeOpacity()
    {
        return $this->_getAttribute('stroke-opacity');
    }

    public function setFillOpacity($opacity)
    {
        return $this->_setAttribute(
            'fill-opacity',
            $this->_normalizeOpacity($opacity)
        );
    }

    public function getFillOpacity()
    {
        return $this->_getAttribute('fill-opacity');
    }
}




// Path data
trait TAttributeModule_PathData
{
    public function setCommands($commands)
    {
        $commands = neon\vector\svg\command\Base::listFactory($commands);
        $string = '';

        foreach ($commands as $command) {
            $string .= $command->toString();
        }

        $this->_setAttribute('d', $string);
        return $this;
    }

    public function getCommands()
    {
        return neon\vector\svg\command\Base::listFactory($this->_getAttribute('d'));
    }

    public function importPathData(IPathDataAttributeModule $path)
    {
        $string = $this->_getAttribute('d');

        foreach ($path->getCommands() as $command) {
            $string .= $command->toString();
        }

        $this->_setAttribute('d', $string);
    }
}



// Point data
trait TAttributeModule_PointData
{
    protected $_points = [];

    public function setPoints($points)
    {
        if (is_string($points)) {
            $points = str_replace(',', ' ', $points);
            $points = explode(' ', $points);
        }

        if (!is_array($points)) {
            $points = [$points];
        }

        $this->_points = [];

        while (!empty($points)) {
            $point = array_shift($points);

            if (is_scalar($point)) {
                if (false !== strpos($point, ',')) {
                    $point = explode(',', trim($point));
                } else {
                    if (isset($points[0]) && false === strpos($points[0], ',')) {
                        $point = [$point, array_shift($points)];
                    } else {
                        $point = [$point, null];
                    }
                }
            }

            if (is_array($point)) {
                $point = core\unit\DisplayPosition::factory(array_shift($point), array_shift($point), true);
            }

            if (!$point instanceof core\unit\IDisplayPosition) {
                throw new InvalidArgumentException(
                    'Invalid point detected in '.$this->getName()
                );
            }

            $this->_points[] = $point;
        }

        if (count($this->_points) < self::MIN_POINTS) {
            throw new InvalidArgumentException(
                $this->getName().' shape elements require at least '.self::MIN_POINTS.' points'
            );
        }

        if (self::MAX_POINTS !== null && count($this->_points) > self::MAX_POINTS) {
            throw new InvalidArgumentException(
                $this->getName().' shape elements require no more than '.self::MAX_POINTS.' points'
            );
        }

        $this->_onSetPoints();

        return $this;
    }

    public function getPoints()
    {
        return $this->_points;
    }

    protected function _onSetPoints()
    {
        $output = [];

        foreach ($this->_points as $point) {
            $output[] = $point->getX().','.$point->getY();
        }

        $this->_setAttribute('points', implode(' ', $output));
    }
}



// Position
trait TAttributeModule_Position
{
    protected $_position;

    public function setPosition($xPosition, $yPosition=null)
    {
        $this->_position = core\unit\DisplayPosition::factory($xPosition, $yPosition, true);
        $this->_setAttribute($this->_getXPositionAttributeName(), $this->_position->getX());
        $this->_setAttribute($this->_getYPositionAttributeName(), $this->_position->getY());

        return $this;
    }

    public function setXPosition($x)
    {
        $this->_position->setX($x);
        return $this->_setAttribute($this->_getXPositionAttributeName(), $this->_position->getX());
    }

    public function getXPosition()
    {
        return $this->_getAttribute($this->_getXPositionAttributeName());
    }

    public function setYPosition($y)
    {
        $this->_position->setY($y);
        return $this->_setAttribute($this->_getYPositionAttributeName(), $this->_position->getY());
    }

    public function getYPosition()
    {
        return $this->_getAttribute($this->_getYPositionAttributeName());
    }

    protected function _getXPositionAttributeName()
    {
        return 'x';
    }

    protected function _getYPositionAttributeName()
    {
        return 'y';
    }
}



// Radius
trait TAttributeModule_Radius
{
    public function setRadius($radius)
    {
        return $this->_setAttribute(
            'r',
            core\unit\DisplaySize::factory($radius, null, true)
        );
    }

    public function getRadius()
    {
        return $this->_getAttribute('r');
    }
}

trait TAttributeModule_2DRadius
{
    public function setRadius($radius)
    {
        return $this->setXRadius($radius)->setYRadius($radius);
    }

    public function setXRadius($radius)
    {
        return $this->_setAttribute(
            'rx',
            core\unit\DisplaySize::factory($radius, null, true)
        );
    }

    public function getXRadius()
    {
        return $this->_getAttribute('rx');
    }

    public function setYRadius($radius)
    {
        return $this->_setAttribute(
            'ry',
            core\unit\DisplaySize::factory($radius, null, true)
        );
    }

    public function getYRadius()
    {
        return $this->_getAttribute('ry');
    }
}





// Structure
trait TAttributeModule_Structure
{
    use TAttributeModule_Clip;
    use TAttributeModule_Conditional;
    use TAttributeModule_Container;
    use TAttributeModule_Core;
    use TAttributeModule_Cursor;
    use TAttributeModule_ExternalResources;
    use TAttributeModule_Filter;
    use TAttributeModule_FilterColor;
    use TAttributeModule_Flood;
    use TAttributeModule_Font;
    use TAttributeModule_Gradient;
    use TAttributeModule_Graphics;
    use TAttributeModule_GraphicalElementEvents;
    use TAttributeModule_Marker;
    use TAttributeModule_Mask;
    use TAttributeModule_Paint;
    use TAttributeModule_PaintOpacity;
    use TAttributeModule_Style;
    use TAttributeModule_Text;
    use TAttributeModule_TextContent;
    use TAttributeModule_Viewport;
}



// Shape
trait TAttributeModule_Shape
{
    use TAttributeModule_Structure;
    use TAttributeModule_Transform;
}


// Style
trait TAttributeModule_Style
{
    public function setClass($class)
    {
        return $this->_setAttribute(
            'class',
            $this->_normalizeIdentifier($class)
        );
    }

    public function getClass()
    {
        return $this->_getAttribute('class');
    }

    public function setStyle($style)
    {
        return $this->_setAttribute(
            'style',
            $this->_normalizeIdentifier($style)
        );
    }

    public function getStyle()
    {
        return $this->_getAttribute('style');
    }
}



// Text
trait TAttributeModule_Text
{
    public function setWritingMode($mode)
    {
        return $this->_setAttribute(
            'writing-mode',
            $this->_normalizeKeyword(
                $mode,
                ['inherit', 'lr', 'lr-tb', 'rl', 'rl-tb', 'tb', 'tb-rl'],
                'writing-mode'
            )
        );
    }

    public function getWritingMode()
    {
        return $this->_getAttribute('writing-mode');
    }
}



// Text content
trait TAttributeModule_TextContent
{
    public function setAlignmentBaseline($baseline)
    {
        return $this->_setAttribute(
            'alignment-baseline',
            $this->_normalizeKeyword(
                $baseline,
                [
                    'after-edge', 'alphabetic', 'auto', 'baseline', 'before-edge', 'central',
                    'hanging', 'ideographic', 'inherit', 'mathematical', 'middle', 'text-after-edge', 'text-before-edge'
                ],
                'alignment-baseline'
            )
        );
    }

    public function getAlignmentBaseline()
    {
        return $this->_getAttribute('alignment-baseline');
    }

    public function setBaselineShift($shift)
    {
        return $this->_setAttribute(
            'baseline-shift',
            $this->_normalizeKeywordOrLength(
                $shift,
                ['baseline', 'inherit', 'sub', 'super']
            )
        );
    }

    public function getBaselineShift()
    {
        return $this->_getAttribute('baseline-shift');
    }

    public function setDirection($direction)
    {
        return $this->_setAttribute(
            'direction',
            $this->_normalizeKeyword(
                $direction,
                ['inherit', 'ltr', 'rtl'],
                'direction'
            )
        );
    }

    public function getDirection()
    {
        return $this->_getAttribute('direction');
    }

    public function setDominantBaseline($baseline)
    {
        return $this->_setAttribute(
            'dominant-baseline',
            $this->_normalizeKeyword(
                $baseline,
                [
                    'alphabetic', 'auto', 'central', 'hanging', 'ideographic', 'inherit', 'mathematical',
                    'middle', 'no-change', 'reset-size', 'text-after-edge', 'text-before-edge', 'use-script'
                ],
                'dominant-baseline'
            )
        );
    }

    public function getDominantBaseline()
    {
        return $this->_getAttribute('dominant-baseline');
    }

    public function setGlyphOrientationHorizontal($orientation)
    {
        return $this->_setAttribute(
            'glyph-orientation-horizontal',
            $this->_normalizeKeywordOrAngle(
                $orientation,
                ['inherit']
            )
        );
    }

    public function getGlyphOrientationHorizontal()
    {
        return $this->_getAttribute('glyph-orientation-horizontal');
    }

    public function setGlyphOrientationVertical($orientation)
    {
        return $this->_setAttribute(
            'glyph-orientation-vertical',
            $this->_normalizeKeywordOrAngle(
                $orientation,
                ['inherit']
            )
        );
    }

    public function getGlyphOrientationVertical()
    {
        return $this->_getAttribute('glyph-orientation-vertical');
    }

    public function setKerning($kerning)
    {
        return $this->_setAttribute(
            'kerning',
            $this->_normalizeKeywordOrLength(
                $kerning,
                ['auto', 'inherit']
            )
        );
    }

    public function getKerning()
    {
        return $this->_getAttribute('kerning');
    }

    public function setLetterSpacing($spacing)
    {
        return $this->_setAttribute(
            'letter-spacing',
            $this->_normalizeKeywordOrLength(
                $spacing,
                ['inherit', 'normal']
            )
        );
    }

    public function getLetterSpacing()
    {
        return $this->_getAttribute('letter-spacing');
    }

    public function setTextAnchor($anchor)
    {
        return $this->_setAttribute(
            'text-anchor',
            $this->_normalizeKeyword(
                $anchor,
                ['end', 'inherit', 'middle', 'start'],
                'text-anchor'
            )
        );
    }

    public function getTextAnchor()
    {
        return $this->_getAttribute('text-anchor');
    }

    public function setTextDecoration($decoration)
    {
        return $this->_setAttribute(
            'text-decoration',
            $this->_normalizeKeyword(
                $decoration,
                ['blink', 'inherit', 'line-through', 'none', 'overline', 'underline'],
                'text-decoration'
            )
        );
    }

    public function getTextDecoration()
    {
        return $this->_getAttribute('text-decoration');
    }

    public function setUnicodeBidi($bidi)
    {
        return $this->_setAttribute(
            'unicode-bidi',
            $this->_normalizeKeyword(
                $bidi,
                ['bidi-override', 'embed', 'inherit', 'normal'],
                'unicode-bidi'
            )
        );
    }

    public function getUnicodeBidi()
    {
        return $this->_getAttribute('unicode-bidi');
    }

    public function setWordSpacing($spacing)
    {
        return $this->_setAttribute(
            'word-spacing',
            $this->_normalizeKeywordOrLength(
                $spacing,
                ['inherit', 'normal']
            )
        );
    }

    public function getWordSpacing()
    {
        return $this->_getAttribute('word-spacing');
    }
}



// Transform
trait TAttributeModule_Transform
{
    public function setTransform($transform)
    {
        return $this->_setAttribute(
            'transform',
            $this->_normalizeIdentifier($transform)
        );
    }

    public function getTransform()
    {
        return $this->_getAttribute('transform');
    }
}



// Viewport
trait TAttributeModule_Viewport
{
    public function setClip($clip)
    {
        return $this->_setAttribute(
            'clip',
            $this->_normalizeKeywordOrIdentifier(
                $clip,
                ['auto', 'inherit']
            )
        );
    }

    public function getClip()
    {
        return $this->_getAttribute('clip');
    }

    public function setOverflow($overflow)
    {
        return $this->_setAttribute(
            'overflow',
            $this->_normalizeKeyword(
                $overflow,
                ['auto', 'hidden', 'inherit', 'scroll', 'visible'],
                'overflow'
            )
        );
    }

    public function getOverflow()
    {
        return $this->_getAttribute('overflow');
    }
}



// ViewBox
trait TAttributeModule_ViewBox
{
    public function setViewBox($viewBox)
    {
        return $this->_setAttribute(
            'viewBox',
            $this->_normalizeIdentifier($viewBox)
        );
    }

    public function getViewBox()
    {
        return $this->_getAttribute('viewBox');
    }
}



// XLink
trait TAttributeModule_XLink
{
    public function setLinkType($type)
    {
        return $this->_setAttribute(
            'xlink:type',
            $this->_normalizeKeyword(
                $type,
                ['simple'],
                'xlink:type'
            )
        );
    }

    public function getLinkType()
    {
        return $this->_getAttribute('xlink:type');
    }

    public function setLinkHref($href)
    {
        return $this->_setAttribute(
            'xlink:href',
            $this->_normalizeIdentifier($href)
        );
    }

    public function getLinkHref()
    {
        return $this->_getAttribute('xlink:href');
    }

    public function setLinkRole($role)
    {
        return $this->_setAttribute(
            'xlink:role',
            $this->_normalizeIdentifier($role)
        );
    }

    public function getLinkRole()
    {
        return $this->_getAttribute('xlink:role');
    }

    public function setLinkArcRole($role)
    {
        return $this->_setAttribute(
            'xlink:arcrole',
            $this->_normalizeIdentifier($role)
        );
    }

    public function getLinkArcRole()
    {
        return $this->_getAttribute('xlink:arcrole');
    }

    public function setLinkTitle($title)
    {
        return $this->_setAttribute(
            'xlink:title',
            $this->_normalizeText($title)
        );
    }

    public function getLinkTitle()
    {
        return $this->_getAttribute('xlink:title');
    }

    public function setLinkShow($show)
    {
        return $this->_setAttribute(
            'xlink:show',
            $this->_normalizeKeyword(
                $show,
                ['new', 'replace', 'embed', 'other', 'none'],
                'xlink:show'
            )
        );
    }

    public function getLinkShow()
    {
        return $this->_getAttribute('xlink:show');
    }

    public function setLinkActuate($actuate)
    {
        return $this->_setAttribute(
            'xlink:actuate',
            $this->_normalizeKeyword(
                $actuate,
                ['onRequest', 'onLoad'],
                'xlink:actuate'
            )
        );
    }

    public function getLinkActuate()
    {
        return $this->_getAttribute('xlink:actuate');
    }
}



// Zoom and pan
trait TAttributeModule_ZoomAndPan
{
    public function setZoomAndPan($zoomAndPan)
    {
        return $this->_setAttribute(
            'zoomAndPan',
            $this->_normalizeKeyword(
                $zoomAndPan,
                ['magnify', 'disable'],
                'zoomAndPan'
            )
        );
    }

    public function getZoomAndPan()
    {
        return $this->_getAttribute('zoomAndPan');
    }
}
