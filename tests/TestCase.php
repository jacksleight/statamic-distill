<?php

namespace Tests;

use JackSleight\StatamicDistill\ServiceProvider;
use JackSleight\StatamicDistill\StillServiceProvider;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getPackageProviders($app)
    {
        return [
            ...parent::getPackageProviders($app),
            StillServiceProvider::class,
        ];
    }
}
