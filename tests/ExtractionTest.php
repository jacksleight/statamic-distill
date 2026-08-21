<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function extractionValue(): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'block' => [
                'text_field' => 'text',
                'textarea_field' => 'textarea',
                'markdown_field' => 'markdown',
                'bard_field' => 'bard',
                'number_field' => 'integer',
            ],
        ]),
    ], [
        makeReplicatorSet('block', [
            'text_field' => 'Plain text',
            'textarea_field' => "Two\nlines",
            'markdown_field' => '**Bold** markdown',
            'bard_field' => [makeParagraph('Bard text')],
            'number_field' => 42,
        ]),
    ], 'builder');
}

it('joins text from every text-like field', function () {
    expect(Distill::text(extractionValue()))
        ->toBe("Plain text Two\nlines Bold markdown\n Bard text");
});

it('ignores fields that are not text-like', function () {
    expect(Distill::text(extractionValue()))->not->toContain('42');
});

it('extracts text from a bard field directly', function () {
    $value = makeValue(['type' => 'bard'], [makeParagraph('Just bard')], 'body');

    expect(Distill::text($value))->toBe('Just bard');
});

it('returns an empty string when there is no text', function () {
    $value = makeValue(['type' => 'replicator', 'sets' => makeSets(['block' => ['number_field' => 'integer']])], [
        makeReplicatorSet('block', ['number_field' => 42]),
    ], 'builder');

    expect(Distill::text($value))->toBe('');
});

it('extracts text from a prepared item collection', function () {
    $items = Distill::query(extractionValue())
        ->type(['value:text', 'value:textarea', 'value:bard', 'value:markdown'])
        ->includeSource(true)
        ->get();

    expect(Distill::extractText($items))->toBe("Plain text Two\nlines Bold markdown\n Bard text");
});

it('collects bard data into one array', function () {
    $value = makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'block' => ['bard_field' => 'bard'],
        ]),
    ], [
        makeReplicatorSet('block', ['bard_field' => [makeParagraph('One')]], id: 'a'),
        makeReplicatorSet('block', ['bard_field' => [makeParagraph('Two')]], id: 'b'),
    ], 'builder');

    $data = Distill::bard($value);

    expect($data)->toHaveCount(2);
    expect($data[0]['type'])->toBe('paragraph');
    expect($data[0]['content'][0]['text'])->toBe('One');
    expect($data[1]['content'][0]['text'])->toBe('Two');
});

it('extracts bard data from a prepared item collection', function () {
    $value = makeValue(['type' => 'bard'], [makeParagraph('Only')], 'body');

    $items = Distill::query($value)->type('value:bard')->includeSource(true)->get();

    expect(Distill::extractBard($items))->toHaveCount(1);
});

it('returns bard data the bard modifiers can render', function () {
    $value = makeValue([
        'type' => 'replicator',
        'sets' => makeSets(['block' => ['bard_field' => 'bard']]),
    ], [
        makeReplicatorSet('block', ['bard_field' => [makeParagraph('Hello')]]),
    ], 'builder');

    expect(Statamic\Statamic::modify(Distill::bard($value))->bardText()->fetch())->toBe('Hello');
});

it('returns nothing for a value with no bard fields', function () {
    $value = makeValue(['type' => 'text'], 'Just text');

    expect(Distill::bard($value))->toBe([]);
});
