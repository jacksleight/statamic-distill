<?php

namespace JackSleight\StatamicDistill;

use Illuminate\Support\Facades\Event;
use JackSleight\StatamicDistill\Search\Manager;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];

    public function register()
    {
        $this->app->singleton(Manager::class, function () {
            return new Manager;
        });
    }

    public function bootAddon()
    {
        if (false) {
            Search\ItemProvider::register();
            Event::subscribe(Search\IndexUpdater::class);
        }
    }
}
