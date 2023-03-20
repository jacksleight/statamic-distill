<?php

namespace JackSleight\StatamicDistill;

use Illuminate\Support\Facades\Event;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];

    public function register()
    {
        if (false) {
            $this->app->singleton(Search\Manager::class, function () {
                return new Search\Manager;
            });
        }
    }

    public function bootAddon()
    {
        if (false) {
            Search\ItemProvider::register();
            Event::subscribe(Search\IndexUpdater::class);
        }
    }
}
