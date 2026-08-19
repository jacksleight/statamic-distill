<?php

namespace JackSleight\StatamicDistill\Modifiers;

use Exception;
use JackSleight\StatamicDistill\Facades\Distill as DistillFacade;
use Statamic\Fields\Value;
use Statamic\Modifiers\Modifier;

class DistillBard extends Modifier
{
    public function index($value, $params, $context)
    {
        if (! $value instanceof Value) {
            if (! is_string($value)) {
                throw new Exception('You must pass the name of the field to distill_bard, not the field value itself');
            }
            $value = $context[$value];
        }

        return DistillFacade::bard($value);
    }
}
