<?php

namespace App\Utils;

use Closure;
use Iterator;
use IteratorAggregate;

/**
 * A utility class to manage list all resources using pagination.
 */
class PaginationAggregator implements IteratorAggregate
{

    /**
     * Summary of __construct
     * @param Closure $func Function to query the data with [$page, $limit] parameters.
     * @param int $limit The limit of elements to query per request.
     * @param int $startPage The starting page number.
     */
    public function __construct(
        private Closure $func,
        private int $limit = 10, 
        private int $startPage = 1
    )
    {
    }

    public function getIterator(): Iterator
    {
        $fetched = false;
        $page = $this->startPage;
        while ($fetched === false) {
            $result = $this->func->call($this, $page, $this->limit)->wait();

            // We reach the full amount of items if we have less items than the limit
            if (count($result) < $this->limit) {
                $fetched = true;
                continue;
            }

            yield from $result;

            ++$page;
        }
    }
}
