<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\payment\stripe2\filter;

use df;
use df\core;
use df\spur;
use df\mint;

class Base implements spur\payment\stripe2\IFilter {

    protected $_limit = 10;
    protected $_startingAfter;
    protected $_endingBefore;

    public static function normalize(spur\payment\stripe2\IFilter &$filter=null, array $extra=null): array {
        if(!$filter) {
            $filter = new static;
        }

        $output = $filter->toArray();

        if($extra !== null) {
            $output = array_merge($output, $extra);
        }

        return $output;
    }

    public function setLimit(?int $limit) {
        if($limit === null) {
            $limit = 10;
        }
        
        if($limit < 1) {
            $limit = 1;
        }

        if($limit > 100) {
            $limit = 100;
        }

        $this->_limit = $limit;
        return $this;
    }

    public function getLimit(): ?int {
        return $this->_limit;
    }


    public function setStartingAfter(?string $id) {
        $this->_startingAfter = $id;
        return $this;
    }

    public function getStartingAfter(): ?string {
        return $this->_startingAfter;
    }


    public function setEndingBefore(?string $id) {
        $this->_endingBefore = $id;
        return $this;
    }

    public function getEndingBefore(): ?string {
        return $this->_endingBefore;
    }


    public function toArray(): array {
        $output = [
            'limit' => $this->_limit,
            'include[]' => 'total_count'
        ];

        if($this->_startingAfter !== null) {
            $output['starting_after'] = $this->_startingAfter;
        }

        if($this->_endingBefore !== null) {
            $output['ending_before'] = $this->_endingBefore;
        }

        return $output;
    }

    public function hasPointer(): bool {
        return $this->_startingAfter !== null || $this->_endingBefore !== null;
    }

    protected function _normalizeDateFilter(array $filter=null): ?array {
        if($filter === null) {
            return null;
        }

        $output = [];

        foreach($filter as $key => $date) {
            switch($key) {
                case 'gt':
                case 'gte':
                case 'lt':
                case 'lte':
                    break;

                default:
                    throw core\Error::EArgument([
                        'message' => 'Invalid date filter',
                        'data' => $filter
                    ]);
            }

            $output[$key] = core\time\Date::factory($date)->toTimestamp();
        }

        return $output;
    }
}
