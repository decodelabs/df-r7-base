<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class NativeResource implements IResourceNode {

    use core\TStringProvider;
    use TNode;

    protected $_type;
    protected $_name;

    public function __construct(IInspector $inspector, $resource) {
        $this->_inspector = $inspector;
        $type = get_resource_type($resource);

        if($type == 'stream') {
            $meta = stream_get_meta_data($resource);

            if(isset($meta['stream_type'])) {
                $type = $meta['stream_type'].' '.$type;
            } else if(isset($meta['wrapper_type'])) {
                $type = $meta['wrapper_type'].' '.$type;
            }
        }

        $this->_type = $type;
        $this->_name = substr((string)$resource, strrpos((string)$resource, '#'));
    }

    public function getType(): string {
        return $this->_type;
    }

    public function getName(): string {
        return $this->_name;
    }

    public function getDataValue() {
        return $this->toString();
    }

    public function toString(): string {
        return '* '.$this->_type.' '.$this->_name;
    }
}
