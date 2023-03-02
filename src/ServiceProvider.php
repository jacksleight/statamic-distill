<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
}

function distill($value)
{
    $data = Distill::from($value)->get();

    dd($data);
}
