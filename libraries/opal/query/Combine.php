<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Combine implements ICombineQuery
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Combine;

    public function __construct(ICombinableQuery $parent, array $fields)
    {
        $this->_parent = $parent;
        $this->_sourceManager = $parent->getSourceManager();

        $this->_source = $this->_sourceManager->newSource(
            $parent->getSource()->getAdapter(),
            $parent->getSourceAlias().'_combine'.count($parent->getCombines())
        );

        $this->addFields(...array_values($fields));
    }
}
