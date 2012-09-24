<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\svg;

use df;
use df\core;
use df\neon;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}

// Interfaces
interface IElement {
	public function getName();
	public function getElementName();
	public function prepareAttributes(IDocument $document);

	public function readXml(\XMLReader $reader);
	public function writeXml(IDocument $document, \XMLWriter $writer);
}


// Attribute modules
interface IAnimationEventAttributeModule {
	public function setOnBeginScript($script);
	public function getOnBeginScript();
	public function setOnEndScript($script);
	public function getOnEndScript();
	public function setOnRepeatScript($script);
	public function getOnRepeatScript();
	public function setOnLoadScript($script);
	public function getOnLoadScript();
}

interface IAspectRatioAttributeModule {
	public function setPreserveAspectRatio($preserve);
	public function getPreserveAspectRatio();
}

interface IBaseProfileAttributeModule {
	public function setBaseProfile($profile);
	public function getBaseProfile();
}

interface IBasicGraphicsAttributeModule {
	public function setDisplay($display);
	public function getDisplay();
	public function setVisibility($visibility);
	public function getVisibility();
}

interface IBasicPaintAttributeModule {
	public function setColor($color);
	public function getColor();
	public function setFill($fill);
	public function getFill();
	public function setFillRule($rule);
	public function getFillRule();
	public function setStroke($stroke);
	public function getStroke();
	public function setStrokeDashArray($dash);
	public function getStrokeDashArray();
	public function setStrokeDashOffset($offset);
	public function getStrokeDashOffset();
	public function setStrokeLineCap($lineCap);
	public function getStrokeLineCap();
	public function setStrokeLineJoin($lineJoin);
	public function getStrokeLineJoin();
	public function setStrokeMiterLimit($limit);
	public function getStrokeMiterLimit();
	public function setStrokeWidth($width);
	public function getStrokeWidth();
	public function setColorRendering($rendering);
	public function getColorRendering();
}

interface IClipAttributeModule {
	public function setClipPath($path);
	public function getClipPath();
	public function setClipRule($rule);
	public function getClipRule();
}

interface IConditionalAttributeModule {
	public function setRequiredFeatures($features);
	public function getRequiredFeatures();
	public function setRequiredExtensions($extensions);
	public function getRequiredExtensions();
	public function setSystemLanguage($lang);
	public function getSystemLanguage();
}

interface IContainerAttributeModule {
	public function setEnableBackground($background);
	public function getEnableBackground();
}

interface ICoreAttributeModule {
	public function setId($id);
	public function getId();
	public function setXmlBase($baseIri);
	public function getXmlBase();
	public function setXmlLang($lang);
	public function getXmlLang();
	public function setXmlSpace($space);
	public function getXmlSpace();
}

interface ICursorAttributeModule {
	public function setCursor($cursor);
	public function getCursor();
}

interface IDimensionAttributeModule {
	public function setDimensions($width, $height);
	public function setWidth($width);
	public function getWidth();
	public function setHeight($height);
	public function getHeight();
}

interface IDocumentEventsAttributeModule {
	public function setOnUnloadScript($script);
	public function getOnUnloadScript();
	public function setOnAbortScript($script);
	public function getOnAbortScript();
	public function setOnErrorScript($script);
	public function getOnErrorScript();
	public function setOnResizeScript($script);
	public function getOnResizeScript();
	public function setOnScrollScript($script);
	public function getOnScrollScript();
	public function setOnZoomScript($script);
	public function getOnZoomScript();
}

interface IExternalResourcesAttributeModule {
	public function setExternalResourcesRequired($required);
	public function getExternalResourcesRequired();
}

interface IFilterAttributeModule {
	public function setFilter($filter);
	public function getFilter();
}

interface IFilterColorAttributeModule {
	public function setColorInterpolationFilters($interpolation);
	public function getColorInterpolationFilters();
	public function setLightingColor($color);
	public function getLightingColor();
}

interface IFloodAttributeModule {
	public function setFloodColor($color);
	public function getFloodColor();
	public function setFloodOpacity($opacity);
	public function getFloodOpacity();
}

interface IFontAttributeModule {
	public function setFontFamily($family);
	public function getFontFamily();
	public function setFontSize($size);
	public function getFontSize();
	public function setFontSizeAdjust($adjust);
	public function getFontSizeAdjust();
	public function setFontStretch($stretch);
	public function getFontStretch();
	public function setFontStyle($style);
	public function getFontStyle();
	public function setFontVariant($variant);
	public function getFontVariant();
	public function setFontWeight($weight);
	public function getFontWeight();
}

interface IGradientAttributeModule {
	public function setStopColor($color);
	public function getStopColor();
	public function setStopOpacity($opacity);
	public function getStopOpacity();
}

interface IGraphicalElementEventsAttributeModule {
	public function setOnFocusInScript($script);
	public function getOnFocusInScript();
	public function setOnFocusOutScript($script);
	public function getOnFocusOutScript();
	public function setOnActivateScript($script);
	public function getOnActivateScript();
	public function setOnClickScript($script);
	public function getOnClickScript();
	public function setOnMouseDownScript($script);
	public function getOnMouseDownScript();
	public function setOnMouseUpScript($script);
	public function getOnMouseUpScript();
	public function setOnMouseOverScript($script);
	public function getOnMouseOverScript();
	public function setOnMouseMoveScript($script);
	public function getOnMouseMoveScript();
	public function setOnMouseOutScript($script);
	public function getOnMouseOutScript();
	public function setOnLoadScript($script);
	public function getOnLoadScript();
}

interface IGraphicsAttributeModule extends IBasicGraphicsAttributeModule {
	public function setImageRendering($rendering);
	public function getImageRendering();
	public function setPointerEvents($events);
	public function getPointerEvents();
	public function setShapeRendering($rendering);
	public function getShapeRendering();
	public function setTextRendering($rendering);
	public function getTextRendering();
}

interface IMarkerAttributeModule {
	public function setMarkerStart($start);
	public function getMarkerStart();
	public function setMarkerMid($mid);
	public function getMarkerMid();
	public function setMarkerEnd($end);
	public function getMarkerEnd();
}

interface IMaskAttributeModule {
	public function setMask($mask);
	public function getMask();
}

interface IPaintAttributeModule extends IBasicPaintAttributeModule {
	public function setColorProfile($profile);
	public function getColorProfile();
	public function setColorInterpolation($interpolation);
	public function getColorInterpolation();
}

interface IPaintOpacityAttributeModule {
	public function setOpacity($opacity);
	public function getOpacity();
	public function setStrokeOpacity($opacity);
	public function getStrokeOpacity();
	public function setFillOpacity($opacity);
	public function getFillOpacity();
}

interface IPathDataAttributeModule {
	public function setCommands($commands);
	public function getCommands();
}

interface IPointDataAttributeModule {
	public function setPoints($points);
	public function getPoints();
}

interface IPositionAttributeModule {
	public function setPosition($x, $y=null);
	public function setXPosition($x);
	public function getXPosition();
	public function setYPosition($y);
	public function getYPosition();
}

interface IRadiusAttributeModule {
	public function setRadius($radius);
	public function getRadius();
}

interface I2DRadiusAttributeModule {
	public function setRadius($radius);
	public function setXRadius($radius);
	public function getXRadius();
	public function setYRadius($radius);
	public function getYRadius();
}

interface IStyleAttributeModule {
	public function setClass($class);
	public function getClass();
	public function setStyle($style);
	public function getStyle();
}

interface ITextAttributeModule {
	public function setWritingMode($mode);
	public function getWritingMode();
}

interface ITextContentAttributeModule {
	public function setAlignmentBaseline($baseline);
	public function getAlignmentBaseline();
	public function setBaselineShift($shift);
	public function getBaselineShift();
	public function setDirection($direction);
	public function getDirection();
	public function setDominantBaseline($baseline);
	public function getDominantBaseline();
	public function setGlyphOrientationHorizontal($orientation);
	public function getGlyphOrientationHorizontal();
	public function setGlyphOrientationVertical($orientation);
	public function getGlyphOrientationVertical();
	public function setKerning($kerning);
	public function getKerning();
	public function setLetterSpacing($spacing);
	public function getLetterSpacing();
	public function setTextAnchor($anchor);
	public function getTextAnchor();
	public function setTextDecoration($decoration);
	public function getTextDecoration();
	public function setUnicodeBidi($bidi);
	public function getUnicodeBidi();
	public function setWordSpacing($spacing);
	public function getWordSpacing();
}

interface ITransformAttributeModule {
	public function setTransform($transform);
	public function getTransform();
}

interface IViewBoxAttributeModule {
	public function setViewBox($viewBox);
	public function getViewBox();
}

interface IViewportAttributeModule {
	public function setClip($clip);
	public function getClip();
	public function setOverflow($overflow);
	public function getOverflow();
}

interface IXLinkAttributeModule {
	public function setLinkType($type);
	public function getLinkType();
	public function setLinkHref($href);
	public function getLinkHref();
	public function setLinkRole($role);
	public function getLinkRole();
	public function setLinkArcRole($role);
	public function getLinkArcRole();
	public function setLinkTitle($title);
	public function getLinkTitle();
	public function setLinkShow($show);
	public function getLinkShow();
	public function setLinkActuate($actuate);
	public function getLinkActuate();
}

interface IZoomAndPanAttributeModule {
	public function setZoomAndPan($zoomAndPan);
	public function getZoomAndPan();
}



// Description
interface IDescriptionProvider {
	public function setTitle($title);
	public function getTitle();
	public function setDescription($description);
	public function getDescription();
}


// Metadata
interface IMetadataProvider {
	public function setMetadata($metadata);
	public function getMetadata();		
}


// Container
interface IContainer extends IDescriptionProvider {
	public function setChildren(array $children);
	public function addChildren(array $children);
	public function addChild(IElement $element);
	public function getChildren();
	public function removeChild(IElement $element);
	public function clearChildren();
}



// Structure
interface IStructure extends
	IElement, 
	IClipAttributeModule,
	IConditionalAttributeModule,
	IContainerAttributeModule,
	ICursorAttributeModule,
	IExternalResourcesAttributeModule,
	IFilterAttributeModule,
	IFilterColorAttributeModule,
	IFloodAttributeModule,
	IFontAttributeModule,
	IGradientAttributeModule,
	IGraphicsAttributeModule,
	IGraphicalElementEventsAttributeModule,
	IMarkerAttributeModule,
	IMaskAttributeModule,
	IPaintAttributeModule,
	IPaintOpacityAttributeModule,
	IStyleAttributeModule,
	ITextAttributeModule,
	ITextContentAttributeModule,
	IViewportAttributeModule
	{}


// Definitions
interface IDefinitionProvider {
	public function getDefinitionsElement();
	public function setDefinitions(array $defs);
	public function addDefinitions(array $defs);
	public function addDefinition(IElement $element);
	public function getDefinitions();
	public function removeDefinition(IElement $element);
	public function clearDefinitions();
}

interface IDefinitionsContainer extends 
	IDefinitionProvider,
	IContainer,
	IStructure,
	IMetadataProvider 
	{}


// Document
interface IDocument extends 
	IContainer,
	IStructure,
	IMetadataProvider,
	IDefinitionProvider,
	IAspectRatioAttributeModule,
	IBaseProfileAttributeModule,
	IDocumentEventsAttributeModule,
	IDimensionAttributeModule,
	IPositionAttributeModule,
	IViewBoxAttributeModule,
	IZoomAndPanAttributeModule,
	IPathProvider
	{

	public function setContentScriptType($type);
	public function getContentScriptType();
	public function setContentStyleType($type);
	public function getContentStyleType();	

	public function setVersion($version);
	public function getVersion();	


	public static function fromXmlString($xml);
	public function toXml($embedded=false);
}


interface IGroup extends 
	IContainer, 
	IStructure,
	IDefinitionProvider,
	IMetadataProvider,
	IPathProvider 
	{}


// Shapes
interface IShape extends 
	IStructure,
	IDescriptionProvider,
	ITransformAttributeModule
	{}




interface ICircle extends IShape, IPositionAttributeModule, IRadiusAttributeModule {}
interface IEllipse extends IShape, IPositionAttributeModule, I2DRadiusAttributeModule {}
interface IImage extends IShape, IAspectRatioAttributeModule, IPositionAttributeModule, IDimensionAttributeModule, IXLinkAttributeModule {}
interface ILine extends IShape, IPointDataAttributeModule {}
interface IPolygon extends IShape, IPointDataAttributeModule {}
interface IPolyline extends IShape, IPointDataAttributeModule {}
interface IRectangle extends IShape, IPositionAttributeModule, IDimensionAttributeModule {}

interface IPath extends IShape, IPathDataAttributeModule {
	public function import(IPath $path);
}

interface IPathProvider {
	public function toPath();
}




// Filters
interface IFilter extends IElement {
	
}


// Commands
interface ICommand extends core\IStringProvider {
	public function isRelative($flag=null);
	public function isAbsolute($flag=null);
}

interface IXPositionAwareCommand extends ICommand {
	public function setX($x);
	public function getX();
}

interface IYPositionAwareCommand extends ICommand {
	public function setY($y);
	public function getY();
}

interface IPositionAwareCommand extends IXPositionAwareCommand, IYPositionAwareCommand {
	public function setPosition($x, $y);
}

interface IRadiusAwareCommand extends ICommand {
	public function setRadius($xRadius, $yRadius=null);
	public function setXRadius($radius);
	public function getXRadius();
	public function setYRadius($radius);
	public function getYRadius();
}

interface IControlPointCommand extends ICommand {
	public function setControl($x, $y);
	public function setControlX($x);
	public function getControlX();
	public function setControlY($y);
	public function getControlY();
}

interface I2ControlPointCommand extends ICommand {
	public function setControl1($x, $y);
    public function setControl1X($x);
	public function getControl1X();
	public function setControl1Y($y);
	public function getControl1Y();
	public function setControl2($x, $y);
	public function setControl2X($x);
	public function getControl2X();
	public function setControl2Y($y);
	public function getControl2Y();
}

interface IRotationAwareCommand extends ICommand {
	public function setAngle($angle);
	public function getAngle();
}



interface IArcCommand extends ICommand, IRadiusAwareCommand, IRotationAwareCommand, IPositionAwareCommand {
	public function isLargeArc($flag=null);
	public function isSweep($flag=null);
}

interface IClosePathCommand extends ICommand {}
interface ICubicCurveCommand extends ICommand, IPositionAwareCommand {}
interface IHorizontalLineCommand extends ICommand, IXPositionAwareCommand {}
interface ILineCommand extends ICommand, IPositionAwareCommand {}
interface IMoveCommand extends ICommand, IPositionAwareCommand {}
interface IQuadraticCurveCommand extends ICommand {}
interface ISmoothCubicCurveCommand extends ICommand {}
interface ISmoothQuadraticCurveCommand extends ICommand {}
interface IVerticalLineCommand extends ICommand, IYPositionAwareCommand {}