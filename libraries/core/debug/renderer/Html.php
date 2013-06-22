<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\renderer;

use df;
use df\core;

class Html extends Base {
    
    public function render() {
        while(ob_get_level()) {
            ob_end_clean();
        }
        
        ob_start();
        require __DIR__.'/html/Template.php';
        return ob_get_clean();
    }
    
    
    protected function _getNodeDescription(core\log\INode $node) {
        switch($node->getNodeType()) {
            case 'dump':
                $object = $node->getObject();
                
                if(is_object($object)) {
                    $output = 'instance of <strong>'.get_class($object).'</strong>';
                    $inheritance = $this->_getObjectInheritance($object);
                    
                    if(!empty($inheritance)) {
                        $output .= ' &gt; <span class="objectInheritance">'.
                            implode(' &gt; ', $inheritance).
                        '</span>';
                    }
                    
                    return $output;
                }  else if(is_array($object)) {
                    return 'Array ('.count($object).')';
                } else if(is_string($object)) {
                    return 'String ('.strlen($object).')';
                } else {
                    return ucfirst(strtolower(getType($object)));
                }
                
            case 'exception':
                $exception = $node->getException();
                $output = '<strong>'.get_class($exception).'</strong>';
                $inheritance = $this->_getObjectInheritance($exception);
                
                if(!empty($inheritance)) {
                    $output .= ' &gt; <span class="objectInheritance">'.
                        implode(' &gt; ', $inheritance).
                    '</span>';
                }
                
                return $output;
                
            case 'group':
                return count($node->getChildren()).' objects';
                
            case 'info':
            case 'todo':
            case 'warning':
            case 'error':
            case 'deprecated':
            case 'stub':
                return $this->esc($node->getMessage());
                
            case 'stackTrace':
                return null;
        }
    }
    
    
    
    protected function _getNodeBody(core\log\INode $node) {
        switch($node->getNodeType()) {
            case 'dump':
                $object = &$node->getObject();
                $inspector = new core\debug\dumper\Inspector();
                $data = $inspector->inspect($object, $node->isDeep());
                return '<span class="dump-body">'.$this->_renderDumpData($inspector, $data).'</span>';
                
            case 'exception':
                $lastException = $exception = $node->getException();
                $output = $this->_renderExceptionMessage($node);
                
                $i = 0;
                
                while($chainedException = $lastException->getPrevious()) {
                    $output .= '<div class="chainedException">'."\n";
                    
                    $output .= $this->_renderExceptionMessage(
                        new core\log\node\Exception($chainedException), 
                        '<strong>Chained exception #'.($i+1).'</strong>: '
                    );
                    
                    $output .= '</div>'."\n";
                    
                    $lastException = $chainedException;
                    $i++;
                }
                
                if($exception instanceof core\IDumpable) {
                    $inspector = new core\debug\dumper\Inspector();
                    $data = $inspector->inspectObjectProperties($exception);
                    
                    $output .= '<div class="exception-data dump-body">'.
                        $this->_renderDumpData($inspector, $data).
                    '</div>';
                }
                
                $output .= '<div class="stack">'.$this->_renderStackTrace($node->getStackTrace()).'</div>';
                return $output;
                
            case 'stackTrace':
                return $this->_renderStackTrace($node);
        }
    }
    
    protected function _renderDumpData(core\debug\dumper\Inspector $inspector, core\debug\dumper\INode $data) {
        // Immutable
        if($data instanceof core\debug\dumper\Immutable) {
            return '<span class="dump-'.$data->getType().'">'.$data->toString().'</span>';
        }
        
        // String
        if($data instanceof core\debug\dumper\String) {
            return '<span class="dump-string">'.$this->esc($data->getValue()).'</span>';
        }
        
        // Number
        if($data instanceof core\debug\dumper\Number) {
            return '<span class="dump-number">'.$data->getValue().'</span>';
        }
        
        // Resource
        if($data instanceof core\debug\dumper\Resource) {
            return '<span class="dump-resource">'.$data->toString().'</span>';
        }
        
        $insCount = $inspector->getInstanceCount();
        
        // Reference
        if($data instanceof core\debug\dumper\Reference) {
            $isArray = $data->isArray();
            
            if($isArray) {
                $refType = 'array';
            } else {
                $refType = 'object';
            }
            
            $output = '<span class="dump-'.$refType.'">'.$data->getType().'</span>(';
            $output .= '<a class="dump-'.$refType.' dump-ref" href="#dump-'.$refType.'-';
            $output .= $insCount.'-'.$data->getDumpId().'">&amp;'.$data->getDumpId().'</a>)';
            
            return $output;
        }
        
        // Structure
        if($data instanceof core\debug\dumper\Structure) {
            $dumpId = $data->getDumpId();
            
            if($data->isArray()) {
                $output = '<span class="dump-array open" id="dump-array-'.$insCount.'-'.$dumpId.'">';
                $output .= 'array</span>';
                
                if($inspector->countArrayRefHits($dumpId)) {
                    $output .= ':<span class="dump-arrayRef">'.$dumpId.'</span>';
                }
            } else {
                $output = '<span class="dump-object open" id="dump-object-'.$insCount.'-'.$dumpId.'">';
                $output .= $data->getType().'</span>';
            
                if($inspector->countObjectHashHits($dumpId)) {
                    $output .= ':<span class="dump-objectRef">'.$dumpId.'</span>';
                }
            }
            
            $output .= '(';
            
            $properties = $data->getProperties();
            $propString = '';
            
            if($propertyCount = count($properties)) {
                $singleEntry = false;
                $maxPropertyLength = 100;
                
                foreach($properties as $property) {
                    if($propertyCount == 1
                    && $property->canInline()) {
                        $singleEntry = true;
                        $value = $property->getValue();
                        
                        if(mb_strlen($value) > $maxPropertyLength) {
                            $singleEntry = false;
                            $propString .= '    ';
                        } else {
                            $propString .= ' ';
                        }
                    } else {
                        $propString .= '    ';
                        
                        if($hasName = $property->hasName()) {
                            $propString .= '[';
                        }
                        
                        if($property->isProtected()) {
                            $propString .= '<span class="protected">±</span>';
                        } else if($property->isPrivate()) {
                            $propString .= '<span class="private">§</span>';
                        }
                        
                        if($hasName) {
                            $propString .= $this->_renderDumpData(
                                $inspector, new core\debug\dumper\String($property->getName())
                            ).'] =&gt; ';
                        }
                    }
                    
                    $propString .= str_replace(
                        "\n", "\n    ", 
                        $this->_renderDumpData($inspector, $property->inspectValue($inspector, $property->isDeep()))
                    );
                    
                    if(!$singleEntry) {
                        $propString .= "\n";
                    } else {
                        $propString .= ' ';
                    }
                }

                if(!$singleEntry) {
                    $propString = "\n".$propString;
                }
                
                $output .= $propString;
            }
            
            $output .= ')';
            return $output;
        }
    }
    
    protected function _renderExceptionMessage(core\log\IExceptionNode $node, $messagePrefix=null) {
        $output  = '<p class="message">'.$messagePrefix.$this->esc($node->getMessage()).'</p>'."\n";
        $output .= '<p class="location">Location: <strong>'.$this->_normalizeLocation($node->getFile(), $node->getLine()).'</strong></p>'."\n";
        $output .= '<p class="call">Triggering call: <strong>'.$this->esc($node->getStackCall()->getSignature()).'</strong></p>'."\n";
        
        return $output;
    }
    
    protected function _renderStackTrace(core\debug\IStackTrace $stackTrace) {
        $output =  '<table class="stack">'."\n";
        $output .= '    <tr>'."\n";
        $output .= '        <th>#</th>'."\n";
        $output .= '        <th>File</th>'."\n";
        $output .= '        <th>Line</th>'."\n";
        $output .= '        <th>Parent call</th>'."\n";
        $output .= '    </tr>'."\n";
        
        $calls = $stackTrace->getCalls();
        $i = count($calls);
        
        foreach($calls as $i => $call) {
            $output .= '    <tr>'."\n";
            $output .= '        <td>'.$this->esc($i).'</td>'."\n";
            $output .= '        <td>'.$this->esc($this->_normalizeFilePath($call->getFile())).'</td>'."\n";
            $output .= '        <td>'.$this->esc($call->getLine()).'</td>'."\n";
            $output .= '        <td>'.$this->esc($call->getSignature(true)).'</td>'."\n";
            $output .= '    </tr>';
            $i--; 
        }
            
        $output .= '</table>'."\n";
        return $output;
    }
    
    public function esc($string, $default=null) {
        if(!strlen($string)) {
            $string = $default;    
        }
        
        $conv = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
        
        if(strlen($conv)) {
            $string = $conv;
        } else {
            $conv = htmlspecialchars($string, ENT_QUOTES);

            if(strlen($conv)) {
                $string = $conv;
            } else {
                $string = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $string);
            }
        }
        
        return $string;
    }
}
