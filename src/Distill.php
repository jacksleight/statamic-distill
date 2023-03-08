<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Items\Distiller;

class Distill
{
    public function from($value)
    {
        return new Distiller($value);
    }
}
