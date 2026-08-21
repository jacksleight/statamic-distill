<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function replicatorWithSets($content): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'first' => [
                'text_field' => 'text',
                'inner_bard' => [
                    'type' => 'bard',
                    'sets' => makeSets(['deep' => ['text_field' => 'text']]),
                ],
            ],
            'second' => [
                'text_field' => 'text',
            ],
        ]),
    ], $content, 'builder');
}

it('collects sets', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('first', ['text_field' => 'One']),
        makeReplicatorSet('second', ['text_field' => 'Two']),
    ]);

    expect(itemMap(Distill::query($value)->get()))->toBe([
        'set:first @ 0',
        'value:text @ 0.text_field',
        'value:bard @ 0.inner_bard',
        'set:second @ 1',
        'value:text @ 1.text_field',
    ]);
});

it('does not collect the id, type or enabled keys', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('second', ['text_field' => 'Two']),
    ]);

    expect(itemPaths(Distill::query($value)->get()))->not->toContain('0.id', '0.type', '0.enabled');
});

it('collects bard sets nested in replicator sets', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('first', [
            'text_field' => 'One',
            'inner_bard' => [
                makeParagraph('Paragraph'),
                makeBardSet('deep', ['text_field' => 'Deep text']),
            ],
        ]),
    ]);

    $items = Distill::query($value)->type('set:deep')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.inner_bard.1');
});

it('skips disabled sets', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('second', ['text_field' => 'On'], enabled: true, id: 'on'),
        makeReplicatorSet('second', ['text_field' => 'Off'], enabled: false, id: 'off'),
    ]);

    expect(Distill::query($value)->type('set:second')->get())->toHaveCount(1);
});

it('includes disabled sets when asked', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('second', ['text_field' => 'On'], enabled: true, id: 'on'),
        makeReplicatorSet('second', ['text_field' => 'Off'], enabled: false, id: 'off'),
    ]);

    expect(Distill::query($value)->includeDisabled(true)->type('set:second')->get())->toHaveCount(2);
});

it('marks included sets as disabled, leaving enabled sets unmarked', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('second', ['text_field' => 'On'], enabled: true, id: 'on'),
        makeReplicatorSet('second', ['text_field' => 'Off'], enabled: false, id: 'off'),
    ]);

    $items = Distill::query($value)->includeDisabled(true)->type('set:second')->get();

    expect($items->map->getQueryableValue('enabled')->all())->toBe([null, false]);
});

it('records the parent set on nested items', function () {
    $value = replicatorWithSets([
        makeReplicatorSet('second', ['text_field' => 'Two']),
    ]);

    $item = Distill::query($value)->type('value:text')->get()->first();

    expect($item->info->parent->info->type)->toBe('set:second');
});
