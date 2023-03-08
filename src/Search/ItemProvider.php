<?php

namespace JackSleight\StatamicDistill\Search;

use Illuminate\Support\Collection;
use JackSleight\StatamicDistill\Facades\Distill;
use JackSleight\StatamicDistill\Items\Item;
use Statamic\Facades\Data;
use Statamic\Facades\Entry;
use Statamic\Search\Searchables\Provider;
use Statamic\Support\Str;

class ItemProvider extends Provider
{
    protected static $handle = 'distill';

    protected static $referencePrefix = 'distill';

    public function find(array $keys): Collection
    {
        return collect($keys)
            ->map(function ($key) {
                $ref = Str::before($key, '#');
                $path = Str::after($key, '#');
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
        $test = Entry::find('7fe0eb7c-ca86-4c40-a1a3-774e7b18aaa3');

        return Distill::from($test)
            ->path('builder.*')
            ->get();
    }

    public function contains($searchable): bool
    {
        return $searchable instanceof Item;
    }
}
