<?php

namespace Tests\Fieldtypes;

use Statamic\Fieldtypes\Assets\Assets;

class CountingAssets extends Assets
{
    protected static $handle = 'assets';

    public static $augmentCount = 0;

    public static function reset(): void
    {
        static::$augmentCount = 0;
    }

    public function augment($value)
    {
        static::$augmentCount++;

        return parent::augment($value);
    }
}
