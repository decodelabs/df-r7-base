<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Dumpable;

class Delete implements IDeleteQuery, Dumpable
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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:*source' => $this->_source->getAdapter();

        if ($this->hasWhereClauses()) {
            yield 'property:*where' => $this->getWhereClauseList();
        }

        if (!empty($this->_order)) {
            $order = [];

            foreach ($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            yield 'property:*order' => implode(', ', $order);
        }

        if ($this->_limit !== null) {
            yield 'property:*limit' => $this->_limit;
        }
    }
}
