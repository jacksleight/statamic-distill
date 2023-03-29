<?php

namespace JackSleight\StatamicDistill;

use JackSleight\StatamicDistill\Items\QueryBuilder;

class Distill
{
    const TYPE_ASSET = 'asset';

    const TYPE_ENTRY = 'entry';

    const TYPE_MARK = 'mark';

    const TYPE_NODE = 'node';

    const TYPE_RAW = 'raw';

    const TYPE_RAW_ARRAY = 'raw:array';

    const TYPE_RAW_BOOLEAN = 'raw:boolean';

    const TYPE_RAW_FLOAT = 'raw:float';

    const TYPE_RAW_INTEGER = 'raw:integer';

    const TYPE_RAW_NULL = 'raw:null';

    const TYPE_RAW_OBJECT = 'raw:object';

    const TYPE_RAW_STRING = 'raw:string';

    const TYPE_ROW = 'row';

    const TYPE_SET = 'set';

    const TYPE_TERM = 'term';

    const TYPE_USER = 'user';

    const TYPE_VALUE = 'value';

    const TYPE_VALUE_ASSETS = 'value:assets';

    const TYPE_VALUE_BARD = 'value:bard';

    const TYPE_VALUE_ENTRIES = 'value:entries';

    const TYPE_VALUE_GRID = 'value:grid';

    const TYPE_VALUE_REPLICATOR = 'value:replicator';

    const TYPE_VALUE_TERMS = 'value:terms';

    const TYPE_VALUE_USERS = 'value:users';

    public function from($value)
    {
        return new QueryBuilder($value);
    }
}
