<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Items\QueryBuilder;

class Distill
{
    const TYPE_ENTRY = 'entry';

    const TYPE_TERM = 'term';

    const TYPE_ASSET = 'asset';

    const TYPE_USER = 'user';

    const TYPE_VALUE = 'value';

    const TYPE_VALUE_ENTRIES = 'value:entries';

    const TYPE_VALUE_TERMS = 'value:terms';

    const TYPE_VALUE_ASSETS = 'value:assets';

    const TYPE_VALUE_USERS = 'value:users';

    const TYPE_VALUE_REPLICATOR = 'value:replicator';

    const TYPE_VALUE_BARD = 'value:bard';

    const TYPE_VALUE_GRID = 'value:grid';

    const TYPE_SET = 'set';

    const TYPE_ROW = 'row';

    const TYPE_NODE = 'node';

    const TYPE_MARK = 'mark';

    public function from($value)
    {
        return new QueryBuilder($value);
    }
}
