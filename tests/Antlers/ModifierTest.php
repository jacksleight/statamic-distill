<?php

use JackSleight\StatamicDistill\Modifiers\DistillBard;
use JackSleight\StatamicDistill\Modifiers\DistillText;
use Statamic\Facades\Antlers;
use Statamic\Fields\Value;

function modifierValue(): Value
{
    return makeValue([
        'type' => 'replicator',
        'sets' => makeSets([
            'block' => ['text_field' => 'text', 'bard_field' => 'bard'],
        ]),
    ], [
        makeReplicatorSet('block', [
            'text_field' => 'Plain',
            'bard_field' => [makeParagraph('Bard')],
        ]),
    ], 'body');
}

it('extracts text from a field name', function () {
    expect((string) Antlers::parse('{{ "body" | distill_text }}', ['body' => modifierValue()], true))
        ->toBe('Plain Bard');
});

it('extracts bard data from a field name', function () {
    expect((string) Antlers::parse('{{ "body" | distill_bard | bard_html }}', ['body' => modifierValue()], true))
        ->toBe('<p>Bard</p>');
});

it('accepts a value object directly in php', function () {
    expect((new DistillText)->index(modifierValue(), [], []))->toBe('Plain Bard');
});

it('rejects anything that is not a value or field name', function () {
    expect(fn () => (new DistillBard)->index(['not' => 'a field'], [], []))
        ->toThrow(Exception::class, 'You must pass the name of the field to distill_bard, not the field value itself');
});
