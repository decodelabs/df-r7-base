<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\string;

use df\core;

class RainbowKey implements IRainbowKey, core\IDumpable {
    
    use core\TStringProvider;
    
    protected $_bytes;
    
    public static function createFromHex($hexItemId, $generatorId='0000', $itemIdSize=8) {
        $itemIdSize = (int)$itemIdSize;
        
        if($itemIdSize < 1) {
            $itemIdSize = 1;
        }
        
        if(is_int($generatorId)) {
            $generatorId = Manipulator::baseConvert($generatorId, 10, 16, 4);
        } else {
            $generatorId = str_pad($generatorId, 4, '0', STR_PAD_LEFT);
        }
        
        $hexSize = 2 * $itemIdSize;
        
        return new self(
            pack('H'.$hexSize.'H4',
                str_pad($hexItemId, $hexSize, '0', STR_PAD_LEFT),
                $generatorId
            )
        );
    }
    
    public static function create($itemId, $generatorId='0000', $itemIdSize=8) {
        $itemIdSize = (int)$itemIdSize;
        
        if($itemIdSize < 1) {
            $itemIdSize = 1;
        }
        
        if(is_int($generatorId)) {
            $generatorId = Manipulator::baseConvert($generatorId, 10, 16, 4);
        } else {
            $generatorId = str_pad($generatorId, 4, '0', STR_PAD_LEFT);
        }
        
        $hexSize = 2 * $itemIdSize;
        
        return new self(
            pack('H'.$hexSize.'H4',
                Manipulator::baseConvert($itemId, 10, 16, $hexSize),
                $generatorId
            )
        );
    }
    
    public static function factory($key, $itemSize=8) {
        if($key instanceof IRainbowKey
        || $key === null) {
            return $key;
        }
        
        if(preg_match('/^[a-f0-9]{'.(2 * $itemSize).'}+\:?[a-f0-9]{4}$/i', $key)) {
            return new self(pack('H*', str_replace(':', '', $key)));
        }  
        
        if(preg_match('/^([0-9]+)\/([a-f0-9]+)$/i', $key, $matches)) {
            return self::create($matches[1], $matches[2], $itemSize);
        }
        
        if(preg_match('/^([a-f0-9]+)\:([a-f0-9]+)$/i', $key, $matches)) {
            return self::createFromHex($matches[1], $matches[2], $itemSize);
        }
        
        if(is_numeric($key) || strlen($key) < $itemSize + 2) {
            return self::create($key, 0xffff, $itemSize);
        }
        
        return new self($key);
    }
    
    public function __construct($bytes) {
        $this->_bytes = $bytes;
    }
    
    public function getBytes() {
        return $this->_bytes;
    }
    
    public function getHex() {
        return bin2hex($this->_bytes);
    }
    
    public function getGeneratorId() {
        return bin2hex(substr($this->_bytes, -2));
    }
    
    public function getItemId() {
        return Manipulator::baseConvert(bin2hex(substr($this->_bytes, 0, -2)), 16, 10);
    }
    
    public function getItemIdSize() {
        return strlen($this->_bytes) - 2;
    }
    
    public function toString() {
        return bin2hex(substr($this->_bytes, 0, -2)).':'.
               bin2hex(substr($this->_bytes, -2));
    }
    
    
// Dump
    public function getDumpProperties() {
        return $this->getItemId().'/'.$this->getGeneratorId().' ('.$this->getItemIdSize().')';
        
        /*
        return array(
            'generatorId' => $this->getGeneratorId(),
            'itemId' => $this->getItemId(),
            'size' => $this->getItemIdSize()
        );
        */
    }
}
