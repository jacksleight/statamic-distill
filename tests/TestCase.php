<?php

namespace JackSleight\StatamicDistill\Tests;

use JackSleight\StatamicDistill\ServiceProvider;
use JackSleight\StatamicDistill\Tests\Fieldtypes\SlowAugment;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

abstract class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        SlowAugment::register();
        SlowAugment::reset();
    }
}
