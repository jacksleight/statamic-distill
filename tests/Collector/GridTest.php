<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function gridValue(array $rows, ?string $dtlType = null): Value
{
    return makeValue(array_filter([
        'type' => 'grid',
        'dtl_type' => $dtlType,
        'fields' => makeFields([
            'text_field' => 'text',
            'number_field' => 'integer',
        ]),
    ]), $rows, 'rows');
}

it('collects rows using the dtl_type', function () {
    $value = gridValue([
        ['id' => 'one', 'text_field' => 'A', 'number_field' => 1],
        ['id' => 'two', 'text_field' => 'B', 'number_field' => 2],
    ], 'thingy');

    expect(itemMap(Distill::query($value)->type('row:*')->get()))->toBe([
        'row:thingy @ 0',
        'row:thingy @ 1',
    ]);
});

it('falls back to an unknown row type', function () {
    $value = gridValue([
        ['id' => 'one', 'text_field' => 'A', 'number_field' => 1],
    ]);

    expect(itemTypes(Distill::query($value)->type('row:*')->get()))->toBe(['row:unknown']);
});

it('collects row values', function () {
    $value = gridValue([
        ['id' => 'one', 'text_field' => 'A', 'number_field' => 1],
    ], 'thingy');

    expect(itemMap(Distill::query($value)->type('value:*')->get()))->toBe([
        'value:text @ 0.text_field',
        'value:integer @ 0.number_field',
    ]);
});

it('does not collect the id key', function () {
    $value = gridValue([
        ['id' => 'one', 'text_field' => 'A', 'number_field' => 1],
    ], 'thingy');

    expect(itemPaths(Distill::query($value)->get()))->not->toContain('0.id');
});
