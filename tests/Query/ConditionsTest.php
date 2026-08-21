<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function conditionsValue(): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'block' => [
                'text_field' => 'text',
                'number_field' => 'integer',
                'optional_field' => 'text',
            ],
        ]),
    ], [
        makeReplicatorSet('block', ['text_field' => 'Charlie', 'number_field' => 3, 'optional_field' => 'set'], id: 'c'),
        makeReplicatorSet('block', ['text_field' => 'Alpha', 'number_field' => 1], id: 'a'),
        makeReplicatorSet('block', ['text_field' => 'Bravo', 'number_field' => 2, 'optional_field' => 'set'], id: 'b'),
    ], 'builder');
}

it('filters with where', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->where('text_field', 'Alpha')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->getQueryableValue('number_field'))->toBe(1);
});

it('filters with an operator', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->where('number_field', '>', 1)->get();

    expect($items)->toHaveCount(2);
});

it('filters with whereIn', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->whereIn('text_field', ['Alpha', 'Bravo'])->get();

    expect($items)->toHaveCount(2);
});

it('filters with whereNotNull', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->whereNotNull('optional_field')->get();

    expect($items)->toHaveCount(2);
});

it('filters with whereNull', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->whereNull('optional_field')->get();

    expect($items)->toHaveCount(1);
});

it('sorts results', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->orderBy('text_field')->get();

    expect($items->map->getQueryableValue('text_field')->all())->toBe(['Alpha', 'Bravo', 'Charlie']);
});

it('limits and offsets results', function () {
    $items = Distill::query(conditionsValue())->type('set:block')->offset(1)->limit(1)->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->getQueryableValue('text_field'))->toBe('Alpha');
});

it('counts results', function () {
    expect(Distill::query(conditionsValue())->type('set:block')->count())->toBe(3);
});

it('paginates results', function () {
    $paginator = Distill::query(conditionsValue())->type('set:block')->paginate(2);

    expect($paginator->total())->toBe(3);
    expect($paginator->items())->toHaveCount(2);
});
