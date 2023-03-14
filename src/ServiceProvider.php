<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Search\ItemProvider;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];

    protected $subscribe = [
        Listeners\IndexUpdater::class,
    ];

    public function bootAddon()
    {
        ItemProvider::register();
    }
}
