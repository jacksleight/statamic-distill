<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Search\ItemProvider;
use JackSleight\StatamicDistill\Search\Manager;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];

    protected $subscribe = [
        Search\IndexUpdater::class,
    ];

    public function register()
    {
        $this->app->singleton(Manager::class, function () {
            return new Manager;
        });
    }

    public function bootAddon()
    {
        ItemProvider::register();
    }
}
