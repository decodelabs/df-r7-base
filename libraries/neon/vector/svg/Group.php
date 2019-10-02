<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg;

use df;
use df\core;
use df\neon;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Group implements IGroup, Inspectable
{
    use TStructure_Container;
    use TStructure_MetaData;
    use TStructure_Definitions;
    use TAttributeModule;
    use TAttributeModule_Structure;

    public function getElementName()
    {
        return 'g';
    }
}
