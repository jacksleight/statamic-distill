<?php

use JackSleight\StatamicDistill\Facades\Distill;

it('collects scalar types', function () {
    $items = Distill::query(['a', 1, 1.5, true, null])->get();

    expect(itemTypes($items))->toBe([
        'raw:string',
        'raw:integer',
        'raw:float',
        'raw:boolean',
        'raw:null',
    ]);
});

it('wraps scalars so they are queryable', function () {
    $items = Distill::query(['a', 'b'])->where('value', 'b')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('1');
});

it('walks nested arrays', function () {
    $items = Distill::query([
        ['id' => 1, 'name' => 'A'],
        ['id' => 2, 'name' => 'B'],
    ])->get();

    expect(itemMap($items))->toBe([
        'raw:array @ 0',
        'raw:integer @ 0.id',
        'raw:string @ 0.name',
        'raw:array @ 1',
        'raw:integer @ 1.id',
        'raw:string @ 1.name',
    ]);
});

it('walks stdClass objects', function () {
    $items = Distill::query([
        (object) ['id' => 1, 'name' => 'A'],
    ])->get();

    expect(itemMap($items))->toBe([
        'raw:object @ 0',
        'raw:integer @ 0.id',
        'raw:string @ 0.name',
    ]);
});

it('types other objects by class', function () {
    $items = Distill::query([new DateTime('2026-01-01')])->get();

    expect(itemTypes($items))->toBe(['class:DateTime']);
});

it('accepts a laravel collection', function () {
    $items = Distill::query(collect(['a', 'b']))->get();

    expect($items)->toHaveCount(2);
});
