<?php

namespace Tests\Fieldtypes;

use Statamic\Fields\Fieldtype;

class Spy extends Fieldtype
{
    protected static $handle = 'spy';

    public static $augmentCount = 0;

    public static function reset(): void
    {
        static::$augmentCount = 0;
    }

    public function augment($value)
    {
        static::$augmentCount++;

        return $value;
    }
}
