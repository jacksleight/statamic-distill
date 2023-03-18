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
        $manager = app(Manager::class);

        $types = $manager->restructureKeys($this->keys);

        $stack = collect();
        foreach ($types as $type => $keys) {
            foreach ($keys as $key => $stills) {
                $sources = $this->getSourceProvider($type, [$key])->provide();
                $items = $manager->processSources($sources, $stills);
                $stack = $stack->concat($items);
            }
        }

        return $stack;
    }

    public function contains($searchable): bool
    {
        if (! $searchable instanceof Item) {
            return false;
        }

        $source = $searchable->info->source;

        if (! $this->getSourceProvider($source)->contains($source)) {
            return false;
        }

        return true;
    }

    protected function getSourceProvider($source, $keys = null)
    {
        if (is_string($source)) {
            return app(Providers::class)
                ->make($source, $this->index, $keys);
        }

        return app(Providers::class)
            ->make(app(Manager::class)->getSourceType($source), $this->index, $keys);
    }
}
