<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
}

function distill($value)
{
    $data = Distill::from($value)
        ->offset(1)
        ->limit(3)
        ->get();

    dd($data);
}
