<?php

namespace JackSleight\StatamicDistill\Stills;

use Statamic\Extend\HasHandle;
use Statamic\Extend\RegistersItself;

abstract class Still
{
    use RegistersItself, HasHandle;

    protected static $binding = 'distill_stills';

    /**
     * Apply the still to a given query builder.
     *
     * @param  \JackSleight\StatamicDistill\Items\QueryBuilder  $query
     * @param  array  $values
     * @return void
     */
    abstract public function apply($query, $values);
}
