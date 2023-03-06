<?php

namespace JackSleight\StatamicDistill;

use Statamic\Query\IteratorBuilder;

class Distiller extends IteratorBuilder
{
    const TYPE_ENTRY = 'entry';

    const TYPE_TERM = 'term';

    const TYPE_ASSET = 'asset';

    const TYPE_USER = 'user';

    const TYPE_FIELD = 'field';

    const TYPE_SET = 'set';

    const TYPE_ROW = 'row';

    const TYPE_NODE = 'node';

    const TYPE_MARK = 'mark';

    const FIELDTYPE_ENTRIES = 'entries';

    const FIELDTYPE_TERMS = 'terms';

    const FIELDTYPE_ASSETS = 'assets';

    const FIELDTYPE_USERS = 'users';

    const FIELDTYPE_REPLICATOR = 'replicator';

    const FIELDTYPE_BARD = 'bard';

    const FIELDTYPE_GRID = 'grid';

    protected $from;

    protected $type;

    protected $path;

    protected $includeRoot = false;

    protected $maxDepth;

    protected $expand;

    public function __construct()
    {
        $this->expand = $this->typeRegex('*');

        return $this;
    }

    public function from($value)
    {
        $this->from = $value;

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
        return (new Collector($this))->collect($this->from);
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

        if (isset($this->type) && ! preg_match($this->type, $item['type'])) {
            return false;
        }

        if (isset($this->path) && ! preg_match($this->path, $item['path'])) {
            return false;
        }

        if (count($this->wheres) && ! $this->filterWheres(collect([$item['data']]))->count()) {
            return false;
        }

        return true;
    }

    public function shouldExpand($item, $depth)
    {
        if (isset($this->maxDepth) && $depth >= $this->maxDepth) {
            return false;
        }

        if (isset($this->expand) && ! preg_match($this->expand, $item['type'])) {
            return false;
        }

        return true;
    }

    public function shouldContinue($count)
    {
        if (isset($this->limit) && $count >= $this->offset + $this->limit) {
            return false;
        }

        return true;
    }

    protected function typeRegex($value)
    {
        if (! is_array($value)) {
            $value = explode('|', $value);
        }

        $regex = [];
        foreach ($value as $item) {
            $parts = preg_split('/(\*)/', $item, -1, PREG_SPLIT_DELIM_CAPTURE);
            $match = [];
            foreach ($parts as $part) {
                $match[] = $part === '*' ? '[^\:]*' : preg_quote($part, '/');
            }
            $regex[] = implode('', $match).'(\:\:.*)?';
        }

        return '/^'.implode('|', $regex).'$/';
    }

    protected function pathRegex($value)
    {
        if (! is_array($value)) {
            $value = explode('|', $value);
        }

        $regex = [];
        foreach ($value as $item) {
            $parts = preg_split('/(\*)/', $item, -1, PREG_SPLIT_DELIM_CAPTURE);
            $match = [];
            foreach ($parts as $part) {
                $match[] = $part === '*' ? '[^\.]*' : preg_quote($part, '/');
            }
            $regex[] = implode('', $match);
        }

        return '/^'.implode('|', $regex).'$/';
    }
}
