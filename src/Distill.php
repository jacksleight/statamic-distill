<?php

namespace JackSleight\StatamicDistill;

class Distill
{
    public function from($value)
    {
        return new Distiller($value);
    }
}
