<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur;

use df;
use df\core;
use df\spur;
use df\link;
use df\flex;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Psr\Http\Message\ResponseInterface;

interface IGuzzleMediator
{
    public function setHttpClient(HttpClient $client);
    public function getHttpClient(): HttpClient;

    public function requestRaw(string $method, string $path, array $data=[], array $headers=[]): ResponseInterface;
    public function requestJson(string $method, string $path, array $data=[], array $headers=[]): core\collection\ITree;
    public function createUrl(string $path): link\http\IUrl;
    public function createRequest(string $method, string $path, array $data=[], array $headers=[]): link\http\IRequest;
    public function sendRequest(link\http\IRequest $request): ResponseInterface;
}




// Data object
interface IDataObject extends core\collection\ITree
{
    public function setType(string $type);
    public function getType(): string;
}


class DataObject extends core\collection\Tree implements IDataObject
{
    protected const PROPAGATE_TYPE = false;

    protected $_type;

    public function __construct(string $type, core\collection\ITree $data, $callback=null)
    {
        parent::__construct();
        $this->setType($type);

        if ($callback) {
            core\lang\Callback::call($callback, $data);
        }

        $this->_collection = $data->_collection;
    }

    public function setType(string $type)
    {
        $this->_type = $type;
        return $this;
    }

    public function getType(): string
    {
        return $this->_type;
    }


    // Serialize
    protected function _getSerializeValues()
    {
        $output = parent::_getSerializeValues();
        $output['ty'] = $this->_type;

        return $output;
    }

    protected function _setUnserializedValues(array $values)
    {
        parent::_setUnserializedValues($values);
        $this->_type = $values['ty'] ?? 'object';
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        parent::glitchInspect($entity, $inspector);

        $entity->setProperty('*type', $this->_type);
    }
}



// List
interface IDataList extends core\IArrayProvider, \IteratorAggregate
{
    public function getTotal(): int;
    public function hasMore(): bool;

    public function setFilter(IFilter $filter);
    public function getFilter(): IFilter;
}



// Filter
interface IFilter extends core\IArrayProvider
{
    public static function normalize(IFilter &$filter=null, callable $callback=null, array $extra=null): array;

    public function setLimit(?int $limit);
    public function getLimit(): ?int;
}

trait TFilter
{
    protected $_limit = null;

    public static function normalize(IFilter &$filter=null, callable $callback=null, array $extra=null): array
    {
        if (!$filter) {
            $filter = new static;
        }

        if ($callback) {
            core\lang\Callback::call($callback, $filter);
        }

        $output = $filter->toArray();

        if ($extra !== null) {
            $output = array_merge($output, $extra);
        }

        return $output;
    }

    public function setLimit(?int $limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->_limit;
    }
}
