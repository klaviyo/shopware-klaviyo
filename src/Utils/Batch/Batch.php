<?php

namespace Klaviyo\Integration\Utils\Batch;

class Batch
{
    private int $batchSize;
    private array $items = [];
    private int $storedItemsCount = 0;
    private \Closure $onFlushClosure;

    public function __construct(int $batchSize, \Closure $onFlushClosure)
    {
        $this->batchSize = $batchSize;
        $this->onFlushClosure = $onFlushClosure;
    }

    public function add($item)
    {
        $this->items[] = $item;
        if (++$this->storedItemsCount >= $this->batchSize) {
            $this->flush();
        }
    }

    public function flush()
    {
        $closure = $this->onFlushClosure;
        $records = $this->items;
        if (count($records) === 0) {
            return;
        }

        $this->clear();

        $closure($records);
    }

    public function clear()
    {
        $this->items = [];
        $this->storedItemsCount = 0;
    }
}