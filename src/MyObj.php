<?php

namespace App;

use Closure;
use GuzzleHttp\Promise\EachPromise;
use Iterator;
use IteratorAggregate;
use Traversable;

/**
 * Summary of MyObj
 */
class MyObj implements IteratorAggregate
{

    /**
     * Summary of __construct
     * @param Iterator|Traversable $query Traversable element to iterate over unprocessed items.
     * @param Closure $processFunc Function returning a Promise processing an item.
     * @param int $concurrent Number of items processed concurrently.
     */
    public function __construct(
        private Iterator|Traversable $query,
        private Closure $processFunc,
        private $concurrent = 2,
    ) {
    }

    /**
     * Generate processing promises in chunks of N ($concurrently) promises.
     */
    public function promises() {
        $count = 0;
        $itemsToProcess = [];

        foreach ($this->query as $items) {
            $itemsToProcess[] = $items;

            if (++$count % $this->concurrent === 0) {
                $promises = array_map(function ($item) {
                    return $this->processFunc->call($this, $item);
                }, $itemsToProcess);
                
                yield $promises;

                $itemsToProcess = [];
            }
        }
    }

    /**
     * Process items.
     * $callback is called everytime an item as been processed.
     */
    public function process(Closure $callback = null) {
        foreach ($this->promises() as $promises) {
            $each = new EachPromise($promises, [
                'concurrency' => $this->concurrent,
                'fulfilled' => $callback
            ]);

            $each->promise()->wait();
        }
    }

    /**
     * Iterate over processed items.
     * Iterations are triggered by batch of concurrent promises.
     */
	public function getIterator(): Iterator {
        foreach ($this->promises() as $promises) {
            $items = [];

            $each = new EachPromise($promises, [
                'concurrency' => $this->concurrent,
                'fulfilled' => function ($item) use (&$items) {
                    $items[] = $item;
                }
            ]);

            $each->promise()->wait();
            yield from $items;
        }
	}
}
