<?php

namespace JackSleight\StatamicDistill\Tests\Concerns;

use Statamic\Fields\Field;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;

trait BuildsBardValues
{
    protected function bardField(array $setHandles = ['heavy']): Field
    {
        $sets = collect($setHandles)
            ->mapWithKeys(fn (string $handle) => [
                $handle => [
                    'fields' => [
                        ['handle' => 'heading', 'field' => ['type' => 'text']],
                        ['handle' => 'heading_type', 'field' => ['type' => 'text']],
                        ['handle' => 'slow', 'field' => ['type' => 'slow_augment']],
                    ],
                ],
            ])
            ->all();

        return new Field('body', [
            'type' => 'bard',
            'sets' => [
                'main' => [
                    'sets' => $sets,
                ],
            ],
        ]);
    }

    protected function bardValue(array $content, ?Field $field = null): Value
    {
        $field ??= $this->bardField();

        return new Value($content, $field->handle(), (new Bard)->setField($field));
    }

    protected function bardHeading(int $level, string $text): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => $level],
            'content' => [['type' => 'text', 'text' => $text]],
        ];
    }

    protected function bardSet(string $type, array $values, ?string $id = null, bool $enabled = true): array
    {
        return [
            'type' => 'set',
            'enabled' => $enabled,
            'attrs' => [
                'id' => $id ?? 'set-'.uniqid(),
                'values' => array_merge(['type' => $type], $values),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function heavyBardContent(int $heavySetCount, ?array $prefix = null): array
    {
        $content = $prefix ?? [
            $this->bardHeading(1, 'Page heading'),
        ];

        for ($i = 0; $i < $heavySetCount; $i++) {
            $content[] = $this->bardSet('heavy', [
                'heading' => 'Heavy '.$i,
                'heading_type' => 'h2',
                'slow' => 'payload-'.$i,
            ], 'heavy-'.$i);
        }

        return $content;
    }
}
