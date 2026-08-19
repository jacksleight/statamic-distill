<?php

namespace JackSleight\StatamicDistill\Tests\Fieldtypes;

use Statamic\Fields\Fieldtype;

class SlowAugment extends Fieldtype
{
    public static int $augmentCalls = 0;

    public static int $augmentDelayMicroseconds = 2000;

    protected static $handle = 'slow_augment';

    public function augment($value)
    {
        self::$augmentCalls++;

        if (self::$augmentDelayMicroseconds > 0) {
            usleep(self::$augmentDelayMicroseconds);
        }

        return $value;
    }

    public static function reset(): void
    {
        self::$augmentCalls = 0;
    }
}
