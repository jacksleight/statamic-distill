<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function expandValue(): Value
{
    return makeValue([
        'type' => 'bard',
        'sets' => makeSets([
            'outer' => [
                'text_field' => 'text',
                'inner_replicator' => [
                    'type' => 'replicator',
                    'sets' => makeSets(['inner' => ['text_field' => 'text']]),
                ],
            ],
        ]),
    ], [
        makeParagraph('Paragraph'),
        makeBardSet('outer', [
            'text_field' => 'Outer text',
            'inner_replicator' => [makeReplicatorSet('inner', ['text_field' => 'Inner text'])],
        ]),
    ], 'body');
}

it('expands sets that do not match the type filter', function () {
    $items = Distill::query(expandValue())->type('set:inner')->get();

    expect(itemPaths($items))->toBe(['1.attrs.values.inner_replicator.0']);
});

it('expands sets when looking for values inside them', function () {
    $items = Distill::query(expandValue())->type('value:text')->get();

    expect(itemPaths($items))->toBe([
        '1.attrs.values.text_field',
        '1.attrs.values.inner_replicator.0.text_field',
    ]);
});

it('only walks into the listed types', function () {
    $items = Distill::query(expandValue())->expand('value:bard')->get();

    expect(itemTypes($items))->toBe(['node:paragraph', 'node:text', 'set:outer']);
});

it('always walks bard node content regardless of expand', function () {
    $items = Distill::query(expandValue())->expand('set:*')->type('node:*')->get();

    expect(itemTypes($items))->toBe(['node:paragraph', 'node:text']);
});

it('stops at sets when replicators are not expanded', function () {
    $items = Distill::query(expandValue())->expand(['value:bard', 'set:*'])->type('set:*')->get();

    expect(itemTypes($items))->toBe(['set:outer']);
});
