<?php

use JackSleight\StatamicDistill\Facades\Distill;

beforeEach(function () {
    makeBenchmarkContent();
});

it('measures scanning a large value', function () {
    $value = makeLargeValue();

    $rows = [
        'everything' => benchmark(fn () => Distill::query($value)->get()),
        'text values' => benchmark(fn () => Distill::query($value)->type('value:text')->get()),
        'one set type' => benchmark(fn () => Distill::query($value)->type('set:text_block')->get()),
        'one set type, depth 1' => benchmark(fn () => Distill::query($value)->type('set:text_block')->depth(1)->get()),
        'first matching set' => benchmark(fn () => Distill::query($value)->type('set:text_block')->limit(1)->get()),
        'assets only' => benchmark(fn () => Distill::query($value)->type('asset:*')->get()),
    ];

    reportBenchmark('Large replicator, with relationships', $rows);

    expect($rows['text values']['count'])->toBeGreaterThan(0);
})->group('benchmark');

it('measures the cost the relationship fields add', function () {
    $with = makeLargeValue(withRelationships: true);
    $without = makeLargeValue(withRelationships: false);

    $rows = [
        'text values, with relations' => benchmark(fn () => Distill::query($with)->type('value:text')->get()),
        'text values, no relations' => benchmark(fn () => Distill::query($without)->type('value:text')->get()),
        'set type, with relations' => benchmark(fn () => Distill::query($with)->type('set:text_block')->get()),
        'set type, no relations' => benchmark(fn () => Distill::query($without)->type('set:text_block')->get()),
    ];

    reportBenchmark('Relationship overhead when nothing relational is wanted', $rows);

    expect($rows['text values, with relations']['count'])
        ->toBe($rows['text values, no relations']['count']);
})->group('benchmark');

it('measures how expand and depth limits help today', function () {
    $value = makeLargeValue();

    $rows = [
        'text values' => benchmark(fn () => Distill::query($value)->type('value:text')->get()),
        'text values, expand sets only' => benchmark(fn () => Distill::query($value)->type('value:text')->expand(['value:replicator', 'set:*'])->get()),
        'text values, max depth 2' => benchmark(fn () => Distill::query($value)->type('value:text')->maxDepth(2)->get()),
        'text values, path scoped' => benchmark(fn () => Distill::query($value)->type('value:text')->path('*.heading')->get()),
    ];

    reportBenchmark('Effect of the existing pruning options', $rows);

    expect($rows['text values, path scoped']['count'])
        ->toBeLessThan($rows['text values']['count']);
})->group('benchmark');
