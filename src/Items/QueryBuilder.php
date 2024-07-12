<?php

namespace JackSleight\StatamicDistill\Items;

use Statamic\Query\IteratorBuilder;
use Statamic\Support\Arr;

class QueryBuilder extends IteratorBuilder
{
    protected $from;

    protected $type;

    protected $path;

    protected $chunks;

    protected $expand;

    protected $includeSource = false;

    protected $minDepth;

    protected $maxDepth;

    protected $unique = false;

    public function __construct($from)
    {
        $this->from = $from;

        return $this;
    }

    public function type($value)
    {
        $this->type = $this->matchRegex($value, ':');

        return $this;
    }

    public function path($value)
    {
        $this->path = $this->matchRegex($value, '.');
        $this->chunks = $this->matchRegex($value, '.', true);

        return $this;
    }

    public function includeSource($value)
    {
        $this->includeSource = $value;

        return $this;
    }

    public function depth($value)
    {
        $this->minDepth = $value;
        $this->maxDepth = $value;

        return $this;
    }

    public function minDepth($value)
    {
        $this->minDepth = $value;

        return $this;
    }

    public function maxDepth($value)
    {
        $this->maxDepth = $value;

        return $this;
    }

    public function unique($value)
    {
        $this->unique = $value;

        return $this;
    }

    public function expand($value)
    {
        $this->expand = $this->matchRegex($value, ':');

        return $this;
    }

    protected function getBaseItems()
    {
        $items = (new Collector($this))->collect($this->from);

        return ItemCollection::make($items);
    }

    protected function getFilteredItems()
    {
        $items = $this->getBaseItems();

        return $items;
    }

    protected function collect($items = [])
    {
        return ItemCollection::make($items);
    }

    public function shouldCollect($item, $depth, $index)
    {
        if (! $this->includeSource && $depth === 0) {
            return false;
        }

        if (isset($this->minDepth) && $depth < $this->minDepth) {
            return false;
        }

        if (isset($this->maxDepth) && $depth > $this->maxDepth) {
            return false;
        }

        if ($this->unique && in_array($item->info->signature, $index)) {
            return false;
        }

        if (isset($this->type) && ! preg_match($this->type, $item->info->type)) {
            return false;
        }

        if (isset($this->path) && ! preg_match($this->path, $item->info->path)) {
            return false;
        }

        if (count($this->wheres) && ! $this->filterWheres(collect([$item]))->count()) {
            return false;
        }

        return true;
    }

    public function shouldExpand($item, $depth)
    {
        if (isset($this->maxDepth) && $depth >= $this->maxDepth) {
            return false;
        }

        if (isset($this->expand) && ! preg_match($this->expand, $item->info->type)) {
            return false;
        }

        if (isset($this->chunks) && ! preg_match($this->chunks, $item->info->path)) {
            return false;
        }

        return true;
    }

    public function shouldContinue($count)
    {
        if (count($this->orderBys)) {
            return true;
        }

        if (isset($this->limit) && $count >= $this->offset + $this->limit) {
            return false;
        }

        return true;
    }

    public function shouldTraverse($path)
    {
        if (isset($this->maxDepth) && count($path) > $this->maxDepth) {
            return false;
        }

        if (isset($this->chunks) && ! preg_match($this->chunks, implode('.', $path))) {
            return false;
        }

        return true;
    }

    protected function matchRegex($value, $delimiter, $chunks = false)
    {
        $regex = [];
        foreach (Arr::wrap($value) as $item) {
            $regex[] = collect(explode($delimiter, $item))
                ->map(function ($part) use ($delimiter) {
                    if ($part === '**') {
                        return '.*';
                    } elseif ($part === '*') {
                        return '[^\\'.$delimiter.']*';
                    }

                    return preg_quote($part, '/');
                })
                ->map(function ($part, $i) use ($delimiter, $chunks) {
                    if ($chunks) {
                        return $i ? '(\\'.$delimiter.$part.')?' : '('.$part.')?';
                    }

                    return $i ? '\\'.$delimiter.$part : $part;
                })
                ->join('');
        }

        return '/^('.implode('|', $regex).')$/';
    }
}
