<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;
use Statamic\Fields\Values;

it('accepts a Values object as the source', function () {
    $values = new Values(['text_field' => 'One', 'number_field' => 2]);

    expect(itemMap(Distill::query($values)->get()))->toBe([
        'raw:string @ text_field',
        'raw:integer @ number_field',
    ]);
})->group('regression');

it('accepts a Collection object as the source', function () {
    expect(Distill::query(collect(['a', 'b']))->get())->toHaveCount(2);
})->group('regression');

it('keeps the fieldtype when extracting text', function () {
    $value = makeValue([
        'type' => 'replicator',
        'sets' => makeSets(['block' => ['text_field' => 'text', 'bard_field' => 'bard']]),
    ], [
        makeReplicatorSet('block', [
            'text_field' => 'Plain',
            'bard_field' => [makeParagraph('Bard')],
        ]),
    ], 'body');

    expect(Distill::text($value))->toBe('Plain Bard');
})->group('regression');

it('includes attrs and values in bard set paths', function () {
    $value = makeValue([
        'type' => 'bard',
        'sets' => makeSets([
            'headings_content' => [
                'blocks' => [
                    'type' => 'replicator',
                    'sets' => makeSets(['heading_lead' => ['heading' => 'text', 'container_classes' => 'text']]),
                ],
            ],
        ]),
    ], [
        makeBardSet('headings_content', [
            'blocks' => [
                makeReplicatorSet('heading_lead', [
                    'heading' => 'Chicago Turkish Festival',
                    'container_classes' => 'col-12',
                ]),
            ],
        ]),
    ], 'body_content');

    $items = Distill::query($value)->type('set:heading_lead')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.attrs.values.blocks.0');
})->group('regression');

it('does not error on whereNotNull', function () {
    $value = makeValue([
        'type' => 'bard',
        'sets' => makeSets([
            'headings_content' => [
                'blocks' => [
                    'type' => 'replicator',
                    'sets' => makeSets(['heading_lead' => ['heading' => 'text', 'container_classes' => 'text']]),
                ],
            ],
        ]),
    ], [
        makeBardSet('headings_content', [
            'blocks' => [
                makeReplicatorSet('heading_lead', ['heading' => 'One', 'container_classes' => 'col-12'], id: 'a'),
                makeReplicatorSet('heading_lead', ['heading' => 'Two'], id: 'b'),
            ],
        ]),
    ], 'body_content');

    $items = Distill::query($value)->type('set:heading_lead')->whereNotNull('container_classes')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->getQueryableValue('heading'))->toBe('One');
})->group('regression');

it('includes content in bard text node paths', function () {
    $value = makeValue(['type' => 'bard'], [
        makeParagraph('This should be translated'),
    ], 'body_content');

    $items = Distill::query($value)->type('node:text')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.content.0');
})->group('regression');

it('records the parent item on collected values', function () {
    $value = makeValue([
        'type' => 'replicator',
        'sets' => makeSets(['block' => ['text_field' => 'text']]),
    ], [
        makeReplicatorSet('block', ['text_field' => 'One']),
    ], 'builder');

    $item = Distill::query($value)->type('value:text')->get()->first();

    expect($item->info->parent)->not->toBeNull();
    expect($item->info->parent->info->type)->toBe('set:block');
})->group('regression');

it('types floats as floats', function () {
    expect(itemTypes(Distill::query([1.2, 1.3, 1.4])->get()))
        ->toBe(['raw:float', 'raw:float', 'raw:float']);
})->group('regression');

it('handles bard fields saved as html strings', function () {
    $value = makeValue(['type' => 'bard', 'save_html' => true], '<p>Already HTML</p>', 'body');

    expect(Distill::query($value)->get())->toHaveCount(0);
    expect(Distill::text($value))->toBe('Already HTML');
})->group('regression');

it('includes the field handle in the info name', function () {
    $value = makeValue([
        'type' => 'replicator',
        'sets' => makeSets(['block' => ['text_field' => 'text']]),
    ], [
        makeReplicatorSet('block', ['text_field' => 'One']),
    ], 'builder');

    expect(Distill::query($value)->type('value:text')->get()->first()->info->name)->toBe('text_field');
})->group('regression');

it('keeps bard set values augmented', function () {
    $value = makeValue([
        'type' => 'bard',
        'sets' => makeSets(['outer' => ['text_field' => 'text']]),
    ], [
        makeBardSet('outer', ['text_field' => 'Set text']),
    ], 'body');

    $items = Distill::query($value)->type('value:text')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->originalValue('value'))->toBeInstanceOf(Value::class);
})->group('regression');

it('expands sets that do not match the type filter', function () {
    $value = makeValue([
        'type' => 'bard',
        'sets' => makeSets([
            'outer' => [
                'inner_replicator' => [
                    'type' => 'replicator',
                    'sets' => makeSets(['inner' => ['text_field' => 'text']]),
                ],
            ],
        ]),
    ], [
        makeBardSet('outer', [
            'inner_replicator' => [makeReplicatorSet('inner', ['text_field' => 'Inner'])],
        ]),
    ], 'body');

    expect(Distill::query($value)->type('set:inner')->get())->toHaveCount(1);
})->group('regression');
