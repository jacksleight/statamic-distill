<?php

namespace JackSleight\StatamicDistill;

use Illuminate\Contracts\Support\DeferrableProvider;
use JackSleight\StatamicDistill\Stills\Still;
use Statamic\Providers\ExtensionServiceProvider;

class StillServiceProvider extends ExtensionServiceProvider implements DeferrableProvider
{
    public function register()
    {
        $types = [
            'distill_stills' => [
                'class' => Still::class,
                'directory' => 'Stills',
            ],
        ];

        foreach ($types as $key => $type) {
            $this->registerBindingAlias($key, $type['class']);
            $this->registerAppExtensions($type['directory'], $type['class']);
        }
    }

    public function provides(): array
    {
        return ['statamic.distill_stills'];
    }
}
