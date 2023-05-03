<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\collection;

use ArrayIterator;
use Closure;
use Countable;
use Iterator;
use IteratorAggregate;

class CachedIterator implements IPageable, IteratorAggregate, Countable
{
    protected ?IPaginator $paginator = null;
    protected IPageable $source;
    protected ?Closure $processor = null;
    protected ?array $cache = null;

    public function __construct(
        IPageable $source,
        ?callable $processor = null
    ) {
        $this->source = $source;
        $this->setProcessor($processor);
        $this->paginator = $source->getPaginator();
    }

    public function setPaginator(?IPaginator $paginator)
    {
        $this->paginator = $paginator;
        return $this;
    }

    public function getPaginator(): ?IPaginator
    {
        return $this->paginator;
    }

    public function setProcessor(?callable $processor)
    {
        $this->processor = $processor ? Closure::fromCallable($processor) : null;
        return $this;
    }

    public function getProcessor(): ?Closure
    {
        return $this->processor;
    }

    public function count(): int
    {
        if ($this->cache === null) {
            $this->getIterator();
        }

        return count($this->cache);
    }

    public function getIterator(): Iterator
    {
        if ($this->cache === null) {
            $this->cache = [];

            if (is_iterable($this->source)) {
                foreach ($this->source as $key => $value) {
                    if ($this->processor) {
                        $value = ($this->processor)($value);
                    }

                    $this->cache[$key] = $value;
                }
            }
        }

        return new ArrayIterator($this->cache);
    }
}
