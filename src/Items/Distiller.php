<?php

namespace JackSleight\StatamicDistill\Items;

use Statamic\Query\IteratorBuilder;
use Statamic\Support\Arr;

class Distiller extends IteratorBuilder
{
    const TYPE_ENTRY = 'entry';

    const TYPE_TERM = 'term';

    const TYPE_ASSET = 'asset';

    const TYPE_USER = 'user';

    const TYPE_VALUE = 'value';

    const TYPE_VALUE_ENTRIES = 'value:entries';

    const TYPE_VALUE_TERMS = 'value:terms';

    const TYPE_VALUE_ASSETS = 'value:assets';

    const TYPE_VALUE_USERS = 'value:users';

    const TYPE_VALUE_REPLICATOR = 'value:replicator';

    const TYPE_VALUE_BARD = 'value:bard';

    const TYPE_VALUE_GRID = 'value:grid';

    const TYPE_SET = 'set';

    const TYPE_ROW = 'row';

    const TYPE_NODE = 'node';

    const TYPE_MARK = 'mark';

    protected $from;

    protected $type;

    protected $path;

    protected $chunks;

    protected $expand;

    protected $includeRoot = false;

    protected $minDepth;

    protected $maxDepth;

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

    public function includeRoot($value)
    {
        $this->includeRoot = $value;

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

    public function shouldCollect($item, $depth)
    {
        if (! $this->includeRoot && $depth === 0) {
            return false;
        }

        if (isset($this->minDepth) && $depth < $this->minDepth) {
            return false;
        }

        if (isset($this->maxDepth) && $depth > $this->maxDepth) {
            return false;
        }

        if (isset($this->type) && ! preg_match($this->type, $item->drop->type)) {
            return false;
        }

        if (isset($this->path) && ! preg_match($this->path, $item->drop->path)) {
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

        if (isset($this->expand) && ! preg_match($this->expand, $item->drop->type)) {
            return false;
        }

        if (isset($this->chunks) && ! preg_match($this->chunks, $item->drop->path)) {
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
