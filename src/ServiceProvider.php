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
        $pro = Addon::get('jacksleight/statamic-distill')->edition() === 'pro';

        if ($pro) {
            $this->app->singleton(Search\Manager::class, function () {
                return new Search\Manager;
            });
        }
    }

    public function bootAddon()
    {
        $pro = Addon::get('jacksleight/statamic-distill')->edition() === 'pro';

        if ($pro) {
            Search\ItemProvider::register();
            Event::subscribe(Search\IndexUpdater::class);
        }
    }
}
