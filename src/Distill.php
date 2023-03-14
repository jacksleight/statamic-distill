<?php

namespace JackSleight\StatamicDistill;

use Illuminate\Support\Collection;
use JackSleight\StatamicDistill\Items\Item;
use JackSleight\StatamicDistill\Items\QueryBuilder;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Support\Str;

class Distill
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

    public function from($value)
    {
        return new QueryBuilder($value);
    }

    public function parseKeys($keys)
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

    public function getSourceStills($source = null)
    {
        $type = collect([
            'collection' => Entry::class,
            'taxonomy' => Term::class,
            'assets' => Asset::class,
            'users' => User::class,
        ])
            ->filter(fn ($class) => $source instanceof $class)
            ->keys()
            ->first();

        $keys = collect(config('statamic.search.indexes'))
            ->pluck('searchables')
            ->flatten()
            ->unique()
            ->map(fn ($key) => Str::after($key, ':'));

        $keys = $this->parseKeys($keys)
            ->only($type)
            ->first();
        if ($type === 'collection') {
            $keys = $keys->only($source->collection()->handle());
        } elseif ($type === 'taxonomy') {
            $keys = $keys->only($source->taxonomy()->handle());
        } elseif ($type === 'assets') {
            $keys = $keys->only($source->container()->handle());
        }

        return $keys->first();
    }

    public function inspectSource($source)
    {
        return collect($source->get('distill_searchables', []))
            ->flip()
            ->map(function ($item, $path) use ($source) {
                return Item::placeholder($source, $path);
            });
    }

    public function processSources(Collection $sources, Collection $stills)
    {
        $items = collect();

        foreach ($sources as $source) {
            $paths = collect();
            foreach ($stills as $handle) {
                $class = app('statamic.distill.stills')->get($handle);
                $still = app($class);
                $query = static::from($source);
                $still->apply($query, []);
                $results = $query->get();
                $items = $items->concat($results);
                $paths = $paths->concat($results->pluck('info.path'));
            }
            $source->set('distill_searchables', $paths->all());
            $source->saveQuietly();
        }

        return $items;
    }

    public function processSource($source)
    {
        $stills = static::getSourceStills($source);

        return static::processSources(collect([$source]), $stills);
    }
}
