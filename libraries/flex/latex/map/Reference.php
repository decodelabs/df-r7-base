<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\map;

use df;
use df\core;
use df\flex;
use df\iris;

use DecodeLabs\Glitch;

class Reference extends iris\map\Node implements flex\latex\IReference
{
    use flex\latex\TReferable;

    protected $_type;

    public function setType($type)
    {
        $this->_type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getTargetType()
    {
        switch ($this->_type) {
            case 'cite':
                return 'bibitem';

            case 'label':
            case 'ref':
                return 'figure';


            default:
                throw Glitch::EUnexpectedValue('Unsupported reference target type', null, $this->_type);
        }
    }

    public function isEmpty(): bool
    {
        return false;
    }
}
