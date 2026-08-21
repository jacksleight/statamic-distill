<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function typedValue(): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'first' => ['text_field' => 'text', 'number_field' => 'integer'],
            'second' => ['text_field' => 'text'],
        ]),
    ], [
        makeReplicatorSet('first', ['text_field' => 'One', 'number_field' => 1]),
        makeReplicatorSet('second', ['text_field' => 'Two']),
    ], 'builder');
}

it('matches an exact type', function () {
    expect(itemTypes(Distill::query(typedValue())->type('set:first')->get()))->toBe(['set:first']);
});

it('matches a wildcard segment', function () {
    expect(itemTypes(Distill::query(typedValue())->type('set:*')->get()))->toBe(['set:first', 'set:second']);
});

it('does not let a single wildcard cross a segment', function () {
    expect(Distill::query(typedValue())->type('*')->get())->toHaveCount(0);
});

it('matches everything with a double wildcard', function () {
    expect(Distill::query(typedValue())->type('**')->get())->toHaveCount(5);
});

it('matches several types when given an array', function () {
    expect(itemTypes(Distill::query(typedValue())->type(['set:second', 'value:integer'])->get()))
        ->toBe(['value:integer', 'set:second']);
});

it('does not split pipe delimited types outside the tag', function () {
    expect(Distill::query(typedValue())->type('set:first|set:second')->get())->toHaveCount(0);
});
