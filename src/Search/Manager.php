<?php

namespace JackSleight\StatamicDistill\Search;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Facades\Data;
use Statamic\Support\Str;

class Manager
{
    protected $current = [];

    public function processSource($source, $stills = null)
    {
        $stills = $stills ?? $this->getSourceStills($source);

        $stack = collect();

        foreach ($stills as $handle) {
            $class = app('statamic.distill.stills')->get($handle);
            $still = app($class);
            $query = Distill::from($source);
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
        $type = collect([
            'collection' => Entry::class,
            'taxonomy' => Term::class,
        ])
            ->filter(fn ($class) => $source instanceof $class)
            ->keys()
            ->first();

        $keys = collect(config('statamic.search.indexes'))
            ->pluck('searchables')
            ->flatten()
            ->unique()
            ->map(fn ($key) => Str::after($key, ':'));

        $keys = $this->restructureKeys($keys)
            ->only($type)
            ->first();
        if ($type === 'collection') {
            $keys = $keys->only($source->collection()->handle());
        } elseif ($type === 'taxonomy') {
            $keys = $keys->only($source->taxonomy()->handle());
        }

        return $keys->first();
    }

    public function restructureKeys($keys)
    {
        return collect($keys)
            ->map(fn ($key) => [
                'prefix' => Str::before($key, ':'),
                'key' => Str::after(Str::beforeLast($key, ':'), ':'),
                'still' => Str::afterLast($key, ':'),
            ])
            ->groupBy('prefix')
            ->only([
                'collection',
                'taxonomy',
                'assets',
                'users',
            ])
            ->map(fn ($group) => collect($group)
                ->groupBy('key')
                ->map(fn ($group) => collect($group)
                    ->pluck('still')));
    }
}
