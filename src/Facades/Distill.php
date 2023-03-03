<?php

namespace JackSleight\StatamicDistill\Facades;

use Illuminate\Support\Facades\Facade;

class Distill extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \JackSleight\StatamicDistill\Distiller::class;
    }
}
