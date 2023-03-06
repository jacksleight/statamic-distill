<?php

namespace JackSleight\StatamicDistill;

use Statamic\Fields\Value;
use Statamic\Query\IteratorBuilder;
use Statamic\Support\Str;

class Distiller extends IteratorBuilder
{
    const TYPE_ENTRY = 'entry';

    const TYPE_TERM = 'term';

    const TYPE_ASSET = 'asset';

    const TYPE_FIELD = 'field';

    const TYPE_SET = 'set';

    const TYPE_ROW = 'row';

    const TYPE_NODE = 'node';

    const TYPE_MARK = 'mark';

    const FIELDTYPE_ENTRIES = 'entries';

    const FIELDTYPE_TERMS = 'terms';

    const FIELDTYPE_ASSETS = 'assets';

    const FIELDTYPE_REPLICATOR = 'replicator';

    const FIELDTYPE_BARD = 'bard';

    const FIELDTYPE_GRID = 'grid';

    protected $walker;

    protected $from;

    protected $type;

    protected $path;

    public function __construct()
    {
        $this->walker = new Walker($this);

        return $this;
    }

    public function from($value)
    {
        $this->from = $value;

        return $this;
    }

    public function type($type)
    {
        if (! is_array($type)) {
            $type = explode('|', $type);
        }

        $regex = collect($type)
            ->map(fn ($type) => ! Str::endsWith($type, '::*') ? "{$type}::*" : $type)
            ->map(function ($type) {
                return collect(preg_split('/(\*)/', $type, -1, PREG_SPLIT_DELIM_CAPTURE))
                    ->map(fn ($part) => $part === '*' ? '[^\:]*' : preg_quote($part, '/'))
                    ->join('');
            })
            ->join('|');

        $this->type = "/^{$regex}/";

        return $this;
    }

    public function path($path)
    {
        if (! is_array($path)) {
            $path = explode('|', $path);
        }

        $regex = collect($path)
            ->map(function ($path) {
                return collect(preg_split('/(\*)/', $path, -1, PREG_SPLIT_DELIM_CAPTURE))
                    ->map(fn ($part) => $part === '*' ? '[^\.]*' : preg_quote($part, '/'))
                    ->join('');
            })
            ->join('|');

        $this->path = "/^{$regex}$/";

        return $this;
    }

    protected function getBaseItems()
    {
        $items = $this->walker->walk($this->from);

        return $this->collect($items);
    }

    protected function getFilteredItems()
    {
        $items = $this->getBaseItems();

        return $items;
    }

    public function shouldContinue($overrun = false)
    {
        if (! isset($this->limit)) {
            return true;
        }

        $limit = $this->offset + $this->limit;

        if ($overrun) {
            $limit++;
        }

        return count($this->walker->items) < $limit;
    }

    public function shouldInclude($item)
    {
        if ($this->type && ! preg_match($this->type, $item['type'])) {
            return false;
        }

        if ($this->path && ! preg_match($this->path, $item['path'])) {
            return false;
        }

        if ($this->wheres) {
            if ($item['value'] instanceof Value) {
                return false;
            }
            if (! $this->filterWheres(collect([$item['value']]))->count()) {
                return false;
            }
        }

        return true;
    }
}
