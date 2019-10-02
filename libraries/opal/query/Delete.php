<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Delete implements IDeleteQuery, Inspectable
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Write;

    public function __construct(ISourceManager $sourceManager, ISource $source)
    {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }

    public function getQueryType()
    {
        return IQueryTypes::DELETE;
    }


    // Execute
    public function execute()
    {
        return $this->_sourceManager->executeQuery($this, function ($adapter) {
            return $adapter->executeDeleteQuery($this);
        });
    }



    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperties([
            '*source' => $inspector($this->_source->getAdapter())
        ]);

        if ($this->hasWhereClauses()) {
            $entity->setProperty('*where', $inspector($this->getWhereClauseList()));
        }

        if (!empty($this->_order)) {
            $order = [];

            foreach ($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            $entity->setProperty('*order', $inspector(implode(', ', $order)));
        }

        if ($this->_limit !== null) {
            $entity->setProperty('*limit', $inspector($this->_limit));
        }
    }
}
