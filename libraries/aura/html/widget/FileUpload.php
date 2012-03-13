<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class FileUpload extends Base implements IFileUploadWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_OptionalMultipleValueInput;
    
    const PRIMARY_TAG = 'input';
    const ARRAY_INPUT = false;
    
    protected $_acceptTypes = array();
    
    public function __construct($name) {
        $this->setName($name);
    }
    
    
    protected function _render() {
        $tag = $this->getTag();
        $tag->setAttribute('type', 'file');
        
        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyOptionalMultipleValueInputAttributes($tag);
        
        if(!empty($this->_acceptTypes)) {
            $tag->setAttribute('accept', implode(',', $this->_acceptTypes));
        }
        
        return $tag;
    }
    
    
    
    
// Accept types
    public function setAcceptTypes($types=null) {
        if($types === null) {
            $this->_acceptTypes = array();
            return $this;
        }
        
        if(!is_array($types)) {
            $types = func_get_args();
        }
        
        $this->_acceptTypes = array();
        
        foreach($types as $type) {
            $type = trim(strtolower($type));
            
            if(!strlen($type)) {
                continue;
            }
            
            $this->_acceptTypes[] = $type;
        }
        
        return $this;
    }
    
    public function getAcceptTypes() {
        return $this->_acceptTypes;
    }
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
