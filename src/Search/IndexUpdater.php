<?php

namespace JackSleight\StatamicDistill\Search;

use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\TermSaving;
use Statamic\Facades\Search;

class IndexUpdater
{
    public function subscribe($event)
    {
        $event->listen(EntrySaving::class, self::class.'@prepare');
        $event->listen(EntrySaved::class, self::class.'@update');
        $event->listen(EntryDeleted::class, self::class.'@delete');
        $event->listen(TermSaving::class, self::class.'@prepare');
        $event->listen(TermSaved::class, self::class.'@update');
        $event->listen(TermDeleted::class, self::class.'@delete');
    }

    public function prepare($event)
    {
        $manager = app(Manager::class);

        $this->sources($event)->each(function ($source) use ($manager) {
            $manager->storeCurrentSource($source);
        });
    }

    public function update($event)
    {
        $manager = app(Manager::class);

        $this->sources($event)->each(function ($source) use ($manager) {
            $current = $manager->fetchCurrentSource($source);
            $updated = $manager->processSource($source);
            $updated
                ->each(function ($item) {
                    Search::updateWithinIndexes($item);
                });
            $current
                ->keyBy('info.path')
                ->except($updated->pluck('info.path')->all())
                ->each(function ($item) {
                    Search::deleteFromIndexes($item);
                });
        });
    }

    public function delete($event)
    {
        $manager = app(Manager::class);

        $this->sources($event)->each(function ($source) use ($manager) {
            $manager->processSource($source)
                ->each(function ($item) {
                    Search::deleteFromIndexes($item);
                });
        });
    }

    private function sources($event)
    {
        $source = $event->entry ?? $event->asset ?? $event->user ?? $event->term;

        if ($source instanceof Term) {
            $sources = $source->localizations();
        } else {
            $sources = collect([$source]);
        }

        return $sources;
    }
}
