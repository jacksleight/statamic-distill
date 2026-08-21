<?php

use Statamic\Fields\Field;
use Statamic\Fields\Value;

function makeValue(array $config, $raw, string $handle = 'field'): Value
{
    $field = new Field($handle, $config);

    return new Value($raw, $handle, $field->fieldtype());
}

function makeFields(array $fields): array
{
    return collect($fields)
        ->map(fn ($config, $handle) => [
            'handle' => $handle,
            'field' => is_string($config) ? ['type' => $config] : $config,
        ])
        ->values()
        ->all();
}

function makeSets(array $sets): array
{
    return ['main' => [
        'sets' => collect($sets)
            ->map(fn ($fields) => ['fields' => makeFields($fields)])
            ->all(),
    ]];
}

function makeText(string $text, array $marks = []): array
{
    return array_filter([
        'type' => 'text',
        'text' => $text,
        'marks' => collect($marks)
            ->map(fn ($mark) => is_string($mark) ? ['type' => $mark] : $mark)
            ->all(),
    ]);
}

function makeParagraph(string|array $content): array
{
    return [
        'type' => 'paragraph',
        'content' => is_string($content) ? [makeText($content)] : $content,
    ];
}

function makeHeading(int $level, string $text): array
{
    return [
        'type' => 'heading',
        'attrs' => ['level' => $level],
        'content' => [makeText($text)],
    ];
}

function makeBardSet(string $type, array $values, bool $enabled = true, ?string $id = null): array
{
    return [
        'type' => 'set',
        'attrs' => array_filter([
            'id' => $id ?? $type.'-id',
            'enabled' => $enabled ? null : false,
            'values' => ['type' => $type] + $values,
        ], fn ($value) => ! is_null($value)),
    ];
}

function makeReplicatorSet(string $type, array $values, bool $enabled = true, ?string $id = null): array
{
    return [
        'id' => $id ?? $type.'-id',
        'type' => $type,
        'enabled' => $enabled,
    ] + $values;
}

function itemTypes($items): array
{
    return collect($items)->map(fn ($item) => $item->info->type)->values()->all();
}

function itemPaths($items): array
{
    return collect($items)->map(fn ($item) => $item->info->path)->values()->all();
}

function itemMap($items): array
{
    return collect($items)
        ->map(fn ($item) => $item->info->type.' @ '.$item->info->path)
        ->values()
        ->all();
}
