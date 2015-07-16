<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\svg;

use df;
use df\core;
use df\neon;
    
class Group implements IGroup, core\IDumpable {

    use TStructure_Container;
    use TStructure_MetaData;
    use TStructure_Definitions;
    use TAttributeModule;
    use TAttributeModule_Structure;

    public function getElementName() {
        return 'g';
    }
}