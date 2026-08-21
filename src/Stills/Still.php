<?php

namespace JackSleight\StatamicDistill\Stills;

use JackSleight\StatamicDistill\Items\QueryBuilder;
use Statamic\Extend\HasHandle;
use Statamic\Extend\RegistersItself;

abstract class Still
{
    use HasHandle, RegistersItself;

    protected static $binding = 'distill.stills';

    /**
     * Apply the still to a given query builder.
     *
     * @param  QueryBuilder  $query
     * @param  array  $values
     * @return void
     */
    abstract public function apply($query, $values);
}
