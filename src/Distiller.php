<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Items\ItemCollection;
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
        $this->type = $this->typeRegex($value);

        return $this;
    }

    public function path($value)
    {
        $this->path = $this->pathRegex($value);

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
        $this->expand = $this->typeRegex($value);

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

        if (isset($this->type) && ! preg_match($this->type, $item->still->type)) {
            return false;
        }

        if (isset($this->path) && ! preg_match($this->path, $item->still->path)) {
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

        if (isset($this->expand) && ! preg_match($this->expand, $item->still->type)) {
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

    protected function typeRegex($value)
    {
        $regex = [];
        foreach (Arr::wrap($value) as $item) {
            $regex[] = collect(preg_split('/(\*)/', $item, -1, PREG_SPLIT_DELIM_CAPTURE))
                ->map(fn ($part) => $part === '*' ? '.*' : preg_quote($part, '/'))
                ->join('');
        }

        return '/^'.implode('|', $regex).'$/';
    }

    protected function pathRegex($value)
    {
        $regex = [];
        foreach (Arr::wrap($value) as $item) {
            $regex[] = collect(preg_split('/(\*)/', $item, -1, PREG_SPLIT_DELIM_CAPTURE))
                ->map(fn ($part) => $part === '*' ? '[^\.]*' : preg_quote($part, '/'))
                ->join('');
        }

        return '/^'.implode('|', $regex).'$/';
    }
}
