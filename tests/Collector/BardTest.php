<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function bardWithSets($content): Value
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
                'inner_bard' => [
                    'type' => 'bard',
                    'sets' => makeSets(['deep' => ['text_field' => 'text']]),
                ],
            ],
        ]),
    ], $content, 'body');
}

it('collects nodes', function () {
    $value = bardWithSets([
        makeHeading(2, 'Heading'),
        makeParagraph('Paragraph'),
    ]);

    expect(itemMap(Distill::query($value)->get()))->toBe([
        'node:heading @ 0',
        'node:text @ 0.content.0',
        'node:paragraph @ 1',
        'node:text @ 1.content.0',
    ]);
});

it('collects marks', function () {
    $value = bardWithSets([
        makeParagraph([makeText('Bold', ['bold'])]),
    ]);

    $items = Distill::query($value)->type('mark')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.content.0.marks.0');
});

it('collects sets', function () {
    $value = bardWithSets([
        makeParagraph('Paragraph'),
        makeBardSet('outer', ['text_field' => 'Set text']),
    ]);

    $items = Distill::query($value)->type('set:outer')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('1');
});

it('augments set values', function () {
    $value = bardWithSets([
        makeBardSet('outer', ['text_field' => 'Set text']),
    ]);

    $items = Distill::query($value)->type('value:text')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.attrs.values.text_field');
    expect($items->first()->originalValue('value')->value())->toBe('Set text');
});

it('collects replicator sets nested in bard sets', function () {
    $value = bardWithSets([
        makeBardSet('outer', [
            'text_field' => 'Set text',
            'inner_replicator' => [
                makeReplicatorSet('inner', ['text_field' => 'Inner text']),
            ],
        ]),
    ]);

    $items = Distill::query($value)->type('set:inner')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.attrs.values.inner_replicator.0');
});

it('collects bard sets nested in bard sets', function () {
    $value = bardWithSets([
        makeBardSet('outer', [
            'inner_bard' => [
                makeBardSet('deep', ['text_field' => 'Deep text']),
            ],
        ]),
    ]);

    $items = Distill::query($value)->type('set:deep')->get();

    expect($items)->toHaveCount(1);
    expect($items->first()->info->path)->toBe('0.attrs.values.inner_bard.0');

    expect(itemPaths(Distill::query($value)->type('value:text')->get()))
        ->toContain('0.attrs.values.inner_bard.0.attrs.values.text_field');
});

it('skips disabled sets', function () {
    $value = bardWithSets([
        makeBardSet('outer', ['text_field' => 'On'], enabled: true, id: 'on'),
        makeBardSet('outer', ['text_field' => 'Off'], enabled: false, id: 'off'),
    ]);

    expect(Distill::query($value)->type('set:outer')->get())->toHaveCount(1);
});

it('includes disabled sets when asked', function () {
    $value = bardWithSets([
        makeBardSet('outer', ['text_field' => 'On'], enabled: true, id: 'on'),
        makeBardSet('outer', ['text_field' => 'Off'], enabled: false, id: 'off'),
    ]);

    expect(Distill::query($value)->includeDisabled(true)->type('set:outer')->get())->toHaveCount(2);
})->skip('include_disabled (#22) is merged, but Statamic still drops disabled sets during augmentation; the bypass is the unmerged 88f9fb5 on feature/include-disabled-sets');

it('collects nothing from a string value', function () {
    $value = bardWithSets('<p>Already HTML</p>');

    expect(Distill::query($value)->get())->toHaveCount(0);
});
