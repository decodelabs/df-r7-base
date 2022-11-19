<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur;

use df\core;
use df\link;

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

interface IGuzzleMediator
{
    public function setHttpClient(HttpClient $client);
    public function getHttpClient(): HttpClient;

    public function requestRaw(string $method, string $path, array $data = [], array $headers = []): ResponseInterface;
    public function requestJson(string $method, string $path, array $data = [], array $headers = []): core\collection\ITree;
    public function createUrl(string $path): link\http\IUrl;
    public function createRequest(string $method, string $path, array $data = [], array $headers = []): link\http\IRequest;
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

    public function __construct(string $type, core\collection\ITree $data, $callback = null)
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
    public function __serialize(): array
    {
        $output = parent::__serialize();
        $output['ty'] = $this->_type;

        return $output;
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->_type = $data['ty'] ?? 'object';
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield from parent::glitchDump();

        yield 'property:*type' => $this->_type;
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
    public static function normalize(IFilter &$filter = null, callable $callback = null, array $extra = null): array;

    public function setLimit(?int $limit);
    public function getLimit(): ?int;
}

trait TFilter
{
    protected $_limit = null;

    public static function normalize(IFilter &$filter = null, callable $callback = null, array $extra = null): array
    {
        if (!$filter) {
            $filter = new static();
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
