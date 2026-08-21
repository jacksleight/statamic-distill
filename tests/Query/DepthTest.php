<?php

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Fields\Value;

function depthValue(): Value
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
            'inner_replicator' => [makeReplicatorSet('inner', ['text_field' => 'Deep'])],
        ]),
    ], 'builder');
}

it('excludes the source by default', function () {
    expect(itemPaths(Distill::query(depthValue())->get()))->not->toContain('');
});

it('includes the source when asked', function () {
    expect(itemTypes(Distill::query(depthValue())->includeSource(true)->get())[0])->toBe('value:replicator');
});

it('limits results to one depth', function () {
    expect(itemPaths(Distill::query(depthValue())->depth(1)->get()))->toBe(['0']);
});

it('limits results to a maximum depth', function () {
    expect(itemPaths(Distill::query(depthValue())->maxDepth(2)->get()))
        ->toBe(['0', '0.text_field', '0.inner_replicator']);
});

it('limits results to a minimum depth', function () {
    expect(itemPaths(Distill::query(depthValue())->minDepth(3)->get()))
        ->toBe(['0.inner_replicator.0', '0.inner_replicator.0.text_field']);
});

it('stops walking at the maximum depth', function () {
    expect(itemPaths(Distill::query(depthValue())->maxDepth(1)->get()))->toBe(['0']);
});
