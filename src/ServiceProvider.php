<?php

namespace JackSleight\StatamicDistill;

use Illuminate\Support\Facades\Event;
use Statamic\Fieldtypes\Grid;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];

    protected $modifiers = [
        Modifiers\DistillBard::class,
        Modifiers\DistillText::class,
    ];

    public function bootAddon()
    {
        Grid::appendConfigField('dtl_type', [
            'type' => 'slug',
            'display' => __('Distill Type'),
            'validate' => 'alpha_dash',
            'separator' => '_',
            'instructions' => __('Used as this field\'s row type in Distill queries'),
            'width' => 50,
        ]);

        $this->app->singleton(Search\Manager::class, function () {
            return new Search\Manager;
        });
        Search\ItemProvider::register();
        Event::subscribe(Search\IndexUpdater::class);
    }
}
