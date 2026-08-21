<?php

use Illuminate\Support\Facades\Storage;
use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Facades\AssetContainer;
use Statamic\Fields\Value;
use Tests\Fieldtypes\CountingAssets;

beforeEach(function () {
    Storage::fake('test');
    AssetContainer::make('media')->disk('test')->save();
    Storage::disk('test')->put('donut.jpg', '');
    Storage::disk('test')->put('pizza.jpg', '');

    CountingAssets::register();
    CountingAssets::reset();
});

function pruningValue(): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'block' => [
                'text_field' => 'text',
                'images' => ['type' => 'assets', 'container' => 'media'],
            ],
        ]),
    ], [
        makeReplicatorSet('block', [
            'text_field' => 'One',
            'images' => ['donut.jpg', 'pizza.jpg'],
        ]),
    ], 'builder');
}

it('does not resolve relationships when no relationship type can match', function () {
    $items = Distill::query(pruningValue())->type('value:text')->get();

    expect($items)->toHaveCount(1);
    expect(CountingAssets::$augmentCount)->toBe(0);
});

it('does not resolve relationships when looking for sets', function () {
    Distill::query(pruningValue())->type('set:block')->get();

    expect(CountingAssets::$augmentCount)->toBe(0);
});

it('does not resolve assets when looking for a different relationship type', function () {
    Distill::query(pruningValue())->type('entry:*')->get();

    expect(CountingAssets::$augmentCount)->toBe(0);
});

it('still resolves relationships when the type can match', function () {
    $items = Distill::query(pruningValue())->type('asset:*')->get();

    expect($items)->toHaveCount(2);
    expect(CountingAssets::$augmentCount)->toBeGreaterThan(0);
});

it('still resolves relationships when there is no type filter', function () {
    Distill::query(pruningValue())->get();

    expect(CountingAssets::$augmentCount)->toBeGreaterThan(0);
});

it('still resolves relationships for a wildcard type', function () {
    Distill::query(pruningValue())->type('**')->get();

    expect(CountingAssets::$augmentCount)->toBeGreaterThan(0);
});
