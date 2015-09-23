<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\fire;

use df;
use df\core;
use df\fire;
use df\aura;
use df\apex;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


class Cache extends core\cache\Base {}


// Interfaces    
interface IManager extends core\IManager {
    public function getConfig();

    public function getCategories();
    public function getCategory($name);

    public function isBlockAvailable($name);
    public function getAllBlocks();
    public function getAllBlockNames();
    public function getAllBlockNamesByFormat();
    public function getBlocksFor($outputType);
    public function getCategoryBlocks($category, $outputType=null);
    public function getCategoryBlockNames($category, $outputType=null);
    public function getCategoryBlockNamesByFormat($category, $outputType=null);
}

interface ICategory {
    public function getName();

    public static function getRequiredOutputTypes();
    public static function getDefaultBlocks();
    public function getDefaultEditorBlockType();
    public static function getFormatWeights();

    public function setBlocks(array $blocks);
    public function addBlocks(array $blocks);
    public function addBlock($block);
    public function hasBlock($block);
    public function getBlocks();
    public function removeBlock($block);

    public function renderBlock(fire\block\IBlock $block, aura\view\IView $view);
    public function renderSlot(fire\slot\IContent $slot, aura\view\IView $view);
}