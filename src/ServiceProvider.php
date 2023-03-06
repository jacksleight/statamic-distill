<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Facades\Asset;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
}

function distill($value)
{
    $term = Term::find('topics::php');
    $asset = Asset::find('assets::blinking-carot.gif');
    $user = User::findByEmail('hi@jacksleight.com');

    $data = Distill::from($value)
        ->get();

    dd($data->all());
}
