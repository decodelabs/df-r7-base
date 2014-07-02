<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\video;

use df;
use df\core;
use df\spur;
use df\aura;

// Exceptions
interface IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}


// Interfaces
interface IVideoEmbed extends aura\html\IElementRepresentation {
    public function setUrl($url);
    public function getUrl();
    public function getProvider();

    public function setWidth($width);
    public function scaleWidth($width);
    public function getWidth();
    public function setHeight($height);
    public function getHeight();
    public function setDimensions($width, $height=null);
    public function shouldAllowFullScreen($flag=null);

    public function setStartTime($seconds);
    public function getStartTime();
    public function setEndTime($seconds);
    public function getEndTime();
    public function setDuration($seconds);
    public function getDuration();

    public function shouldAutoPlay($flag=null);
}