<?php

namespace JackSleight\StatamicDistill\Search;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Data;
use Statamic\Support\Str;

class Manager
{
    protected $mapping = [
        'collection' => Entry::class,
        'taxonomy' => Term::class,
    ];

    protected $current = [];

    public function processSource($source, $stills = null)
    {
        $stills = $stills ?? $this->getSourceStills($source);

        $stack = collect();

        foreach ($stills as $handle) {
            $class = app('statamic.distill.stills')->get($handle);
            $still = app($class);
            $query = Distill::query($source);
            $still->apply($query, []);
            $items = $query->get();
            $stack = $stack->concat($items);
        }

        return $stack;
    }

    public function processSources($sources, $stills)
    {
        $stack = collect();

        foreach ($sources as $source) {
            $items = $this->processSource($source, $stills);
            $stack = $stack->concat($items);
        }

        return $stack;
    }

    public function processCurrentSource($source)
    {
        $current = Data::find($source->reference());

        return $this->processSource($current);
    }

    public function storeCurrentSource($source)
    {
        $this->current[$source->reference()] = $this->processCurrentSource($source);
    }

    public function fetchCurrentSource($source)
    {
        return $this->current[$source->reference()] ?? collect();
    }

    public function getSourceStills($source = null)
    {
        $type = $this->getSourceType($source);

        $keys = collect(config('statamic.search.indexes'))
            ->pluck('searchables')
            ->flatten()
            ->unique()
            ->filter(fn ($key) => Str::startsWith($key, 'distill:'))
            ->map(fn ($key) => Str::after($key, ':'));
        if (! $keys->count()) {
            return [];
        }

        $keys = $this->restructureKeys($keys)->only($type);
        if (! $keys->count()) {
            return [];
        }

        if ($type === 'collection') {
            $keys = $keys->first()->only($source->collection()->handle());
        } elseif ($type === 'taxonomy') {
            $keys = $keys->first()->only($source->taxonomy()->handle());
        }
        if (! $keys->count()) {
            return [];
        }

        return $keys->first() ?? [];
    }

    public function restructureKeys($keys)
    {
        return collect($keys)
            ->map(fn ($key) => [
                'type' => Str::before($key, ':'),
                'key' => Str::after(Str::beforeLast($key, ':'), ':'),
                'still' => Str::afterLast($key, ':'),
            ])
            ->groupBy('type')
            ->only(array_keys($this->mapping))
            ->map(fn ($group) => collect($group)
                ->groupBy('key')
                ->map(fn ($group) => collect($group)
                    ->pluck('still')));
    }

    public function getSourceType($source)
    {
        return collect($this->mapping)
            ->filter(fn ($class) => $source instanceof $class)
            ->keys()
            ->first();
    }
}
