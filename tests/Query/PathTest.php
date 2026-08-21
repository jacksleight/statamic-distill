<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function pathValue(): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'first' => [
                'text_field' => 'text',
                'inner_replicator' => [
                    'type' => 'replicator',
                    'sets' => makeSets(['inner' => ['text_field' => 'text']]),
                ],
            ],
        ]),
    ], [
        makeReplicatorSet('first', [
            'text_field' => 'One',
            'inner_replicator' => [makeReplicatorSet('inner', ['text_field' => 'Deep one'])],
        ], id: 'a'),
        makeReplicatorSet('first', [
            'text_field' => 'Two',
            'inner_replicator' => [makeReplicatorSet('inner', ['text_field' => 'Deep two'])],
        ], id: 'b'),
    ], 'builder');
}

it('matches an exact path', function () {
    expect(itemPaths(Distill::query(pathValue())->path('1.text_field')->get()))->toBe(['1.text_field']);
});

it('matches a wildcard segment', function () {
    expect(itemPaths(Distill::query(pathValue())->path('*.text_field')->get()))
        ->toBe(['0.text_field', '1.text_field']);
});

it('matches any depth with a double wildcard', function () {
    expect(itemPaths(Distill::query(pathValue())->path('**.text_field')->get()))->toBe([
        '0.text_field',
        '0.inner_replicator.0.text_field',
        '1.text_field',
        '1.inner_replicator.0.text_field',
    ]);
});

it('matches several paths when given an array', function () {
    expect(itemPaths(Distill::query(pathValue())->path(['0.text_field', '1.text_field'])->get()))
        ->toBe(['0.text_field', '1.text_field']);
});

it('prunes traversal to the matching branch', function () {
    $items = Distill::query(pathValue())->path('0.inner_replicator.*')->get();

    expect(itemPaths($items))->toBe(['0.inner_replicator.0']);
});
