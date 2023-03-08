<?php

namespace JackSleight\StatamicDistill;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $tags = [
        Tags\Distill::class,
    ];
}
