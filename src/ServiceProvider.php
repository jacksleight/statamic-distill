<?php

namespace JackSleight\StatamicDistill;

use Illuminate\Support\Facades\Event;
use Statamic\Facades\Addon;
use Statamic\Fieldtypes\Grid;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];

    protected $modifiers = [
        Modifiers\DistillBard::class,
    ];

    public function bootAddon()
    {
        $pro = Addon::get('jacksleight/statamic-distill')->edition() === 'pro';

        Grid::appendConfigField('dtl_type', [
            'type' => 'slug',
            'display' => __('Distill Type'),
            'validate' => 'alpha_dash',
            'separator' => '_',
            'instructions' => __('Used as this field\'s row type in Distill queries'),
            'width' => 50,
        ]);

        if ($pro) {
            $this->app->singleton(Search\Manager::class, function () {
                return new Search\Manager;
            });
            Search\ItemProvider::register();
            Event::subscribe(Search\IndexUpdater::class);
        }
    }
}
