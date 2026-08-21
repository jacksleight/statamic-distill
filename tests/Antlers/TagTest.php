<?php

use Statamic\Facades\Antlers;
use Statamic\Fields\Value;

function tagValue(): Value
{
    return makeValue([
        'type' => 'bard',
        'sets' => makeSets([
            'outer' => [
                'text_field' => 'text',
                'number_field' => 'integer',
            ],
        ]),
    ], [
        makeParagraph('Paragraph text'),
        makeBardSet('outer', ['text_field' => 'Alpha', 'number_field' => 1], id: 'a'),
        makeBardSet('outer', ['text_field' => 'Bravo', 'number_field' => 2], id: 'b'),
    ], 'body');
}

function render(string $template, ?Value $value = null): string
{
    return (string) Antlers::parse($template, ['body' => $value ?? tagValue()], true);
}

it('loops over items with the wildcard tag', function () {
    expect(render('{{ distill:body type="set:*" }}[{{ text_field }}]{{ /distill:body }}'))
        ->toBe('[Alpha][Bravo]');
});

it('loops over items with the from parameter', function () {
    expect(render('{{ distill from="body" type="set:*" }}[{{ text_field }}]{{ /distill }}'))
        ->toBe('[Alpha][Bravo]');
});

it('splits pipe delimited types', function () {
    expect(render('{{ distill:body type="node:paragraph|set:outer" }}[{{ info:type }}]{{ /distill:body }}'))
        ->toBe('[node:paragraph][set:outer][set:outer]');
});

it('exposes the info object', function () {
    expect(render('{{ distill:body type="set:*" }}[{{ info:path }}]{{ /distill:body }}'))
        ->toBe('[1][2]');
});

it('applies where conditions from parameters', function () {
    expect(render('{{ distill:body type="set:*" :number_field:is="2" }}[{{ text_field }}]{{ /distill:body }}'))
        ->toBe('[Bravo]');
});

it('applies sort and limit parameters', function () {
    expect(render('{{ distill:body type="set:*" sort="text_field:desc" limit="1" }}[{{ text_field }}]{{ /distill:body }}'))
        ->toBe('[Bravo]');
});

it('counts items', function () {
    expect(render('{{ distill:count from="body" type="set:*" }}'))->toBe('2');
});

it('extracts text', function () {
    expect(render('{{ distill:text from="body" }}'))->toBe('Paragraph text Alpha Bravo');
});

it('extracts bard data for the bard modifiers', function () {
    $value = makeValue([
        'type' => 'replicator',
        'sets' => makeSets(['block' => ['bard_field' => 'bard']]),
    ], [
        makeReplicatorSet('block', ['bard_field' => [makeParagraph('Hello')]]),
    ], 'body');

    expect(render('{{ { distill:bard from="body" } | bard_html }}', $value))
        ->toBe('<p>Hello</p>');
});

it('paginates items', function () {
    $template = <<<'ANTLERS'
{{ distill:body type="set:*" paginate="1" }}{{ items }}[{{ text_field }}]{{ /items }}{{ paginate }}{{ current_page }}/{{ total_pages }}{{ /paginate }}{{ /distill:body }}
ANTLERS;

    expect(render($template))->toBe('[Alpha]1/2');
});
