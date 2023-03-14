<?php

namespace JackSleight\StatamicDistill\Listeners;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetSaved;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\TermDeleted;
use Statamic\Events\TermSaved;
use Statamic\Events\UserDeleted;
use Statamic\Events\UserSaved;
use Statamic\Facades\Search;

class IndexUpdater
{
    public function subscribe($event)
    {
        $event->listen(EntrySaved::class, self::class.'@update');
        $event->listen(EntryDeleted::class, self::class.'@delete');
        $event->listen(AssetSaved::class, self::class.'@update');
        $event->listen(AssetDeleted::class, self::class.'@delete');
        $event->listen(UserSaved::class, self::class.'@update');
        $event->listen(UserDeleted::class, self::class.'@delete');
        $event->listen(TermSaved::class, self::class.'@update');
        $event->listen(TermDeleted::class, self::class.'@delete');
    }

    public function update($event)
    {
        $this->sources($event)->each(function ($source) {
            $current = Distill::inspectSource($source);
            $updated = Distill::processSource($source);
            $updated
                ->each(function ($item) {
                    Search::updateWithinIndexes($item);
                });
            $current
                ->except($updated->pluck('info.path')->all())
                ->each(function ($item) {
                    Search::deleteFromIndexes($item);
                });
        });
    }

    public function delete($event)
    {
        $this->sources($event)->each(function ($source) {
            Distill::getPlaceholderItems($source)
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
