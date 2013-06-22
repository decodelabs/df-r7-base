<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\renderer;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/debug/renderer/Base');

class PlainText extends Base {
    
    protected $_eol = "\n";
    protected $_indent = 2;
    protected $_lineLength = 140;
    
    
    public function setEol($eol) {
        $this->_eol = (string)$eol;
        return $this;
    }
    
    public function getEol() {
        return $this->_eol;
    }
    
    public function setLineLength($length) {
        $this->_lineLength = (int)$length;
        return $this;
    }
    
    public function getLineLength() {
        return $this->_lineLength;
    }
    
    public function setIndent($indent) {
        $this->_indent = (int)$indent;
        return $this;
    }
    
    public function getIndent() {
        return $this->_indent;
    }
    
    public function render() {
        $output = '';
        $output .= $this->_renderGroup($this->_context);
        
        return $output;
    }

    public function renderStats() {
        $output = [];

        foreach($this->_stats as $key => $value) {
            $output[] = $key.': '.$value;
        }

        return implode(', ', $output);
    }
    
    protected function _renderGroup(core\log\IGroupNode $group, $depth=0) {
        $indent = str_repeat('#', $depth * $this->_indent);
        $output = '';
        $lineLength = $this->_lineLength - strlen($indent);
        
        foreach($group->getChildren() as $node) {
            $block = $indent.'|'.str_repeat('-', $lineLength - 1).$this->_eol; 
            $block .= $indent.'| '.$node->getNodeTitle().' - '.$this->_getNodeLocation($node).$this->_eol;
            
            if($node instanceof core\log\IMessageNode) {
                // Message
                $message = wordwrap($node->getMessage(), $lineLength - 5, $this->_eol.$indent.'| > ');
                $block .= $indent.'| > '.$message.$this->_eol;
                
            } else if($node instanceof core\log\IStubNode) {
                // Stub
                $message = wordwrap($node->getMessage(), $lineLength - 5, $this->_eol.$indent.'| > ');
                $block .= $indent.'| > '.$message.$this->_eol;
                
            } else if($node instanceof core\log\IExceptionNode) {
                // Exception
                $message = wordwrap($node->getMessage(), $lineLength - 5, $this->_eol.$indent.'| > ');
                $block .= $indent.'| > '.$message.$this->_eol;
                $block .= $this->_renderGroup(
                    (new core\log\node\Group('exception'))->addChild($node->getStackTrace()),
                    $depth + 1
                );
                
            } else if($node instanceof core\log\IDumpNode) {
                // Dump
                df\Launchpad::loadBaseClass('core/debug/dumper/Inspector');
                $inspector = new core\debug\dumper\Inspector();

                $object = $node->getObject();
                $data = $inspector->inspect($object, $node->isDeep());
                $block .= $indent.'| '.str_replace("\n", $this->_eol.$indent.'| ', $data->toString()).$this->_eol;
                
            } else if($node instanceof core\log\IStackTrace) {
                // Stack trace
                foreach($node->toArray() as $stackCall) {
                    $location = $this->_normalizeLocation($stackCall->getFile(), $stackCall->getLine());
                    $signature = $stackCall->getSignature();
                    $line = $indent.'| > '.$location;
                    
                    $repLength = $lineLength - (strlen($line) + strlen($signature));
                    
                    if($repLength < 1) {
                        $repLength = 1;
                    }

                    $line .= str_repeat(' ', $repLength);
                    $block .= $line.$signature.$this->_eol;
                }
            }
            
            $output .= $block;
            
            if($node instanceof core\log\IGroupNode && $node->hasChildren()) {
                $output .= $this->_renderGroup($node, $depth + 1);
            }
        }
        
        return $output;
    }
}
