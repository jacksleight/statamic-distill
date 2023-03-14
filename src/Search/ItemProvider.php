<?php

namespace JackSleight\StatamicDistill\Search;

use Illuminate\Support\Collection;
use JackSleight\StatamicDistill\Facades\Distill;
use JackSleight\StatamicDistill\Items\Item;
use Statamic\Facades\Data;
use Statamic\Search\Searchables\Provider;
use Statamic\Search\Searchables\Providers;
use Statamic\Support\Str;

class ItemProvider extends Provider
{
    protected static $handle = 'distill';

    protected static $referencePrefix = 'distill';

    public function find(array $keys): Collection
    {
        // @todo optimise to fetch all of same type at once
        return collect($keys)
            ->map(function ($key) {
                $ref = Str::beforeLast($key, '::');
                $path = Str::afterLast($key, '::');
                $object = Data::find($ref);
                $item = Distill::from($object)
                    ->path($path)
                    ->get()
                    ->first();

                return $item;
            });
    }

    public function provide(): Collection
    {
        $types = Distill::parseKeys($this->keys);

        $items = collect();
        foreach ($types as $prefix => $keys) {
            foreach ($keys as $key => $stills) {
                $sources = app(Providers::class)
                    ->make($prefix, $this->index, [$key])
                    ->provide();
                $results = Distill::processSources($sources, $stills);
                $items = $items->concat($results);
            }
        }

        return $items;
    }

    public function contains($searchable): bool
    {
        return $searchable instanceof Item;
    }
}
