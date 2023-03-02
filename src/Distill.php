<?php

namespace JackSleight\StatamicDistill;

use Statamic\Contracts\Entries\Entry;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Fieldtypes\Replicator;
use Statamic\Structures\Page;
use Statamic\Support\Arr;

class Distill
{
    const TYPE_NODE = 'node';

    const TYPE_MARK = 'mark';

    const TYPE_SET = 'set';

    const TYPE_ROW = 'row';

    const TYPE_FIELD = 'field';

    const TYPE_ENTRY = 'entry';

    const TYPE_TERM = 'term';

    const TYPE_ASSET = 'asset';

    const TYPE_RAW = 'raw';

    const SUBTYPE_REPLICATOR = 'replicator';

    const SUBTYPE_BARD = 'bard';

    const SUBTYPE_GRID = 'grid';

    protected $value;

    protected $collection;

    public function from($value)
    {
        $this->value = $value;

        return $this;
    }

    public function get()
    {
        $this->collection = collect();
        $this->collect($this->value);

        return $this->collection;
    }

    protected function collect($value, $path = [], $type = null)
    {
        if ($value instanceof Page) {
            $value = $value->entry();
        }

        $subtype = null;

        if (! $type) {
            if ($value instanceof Value) {
                $type = self::TYPE_FIELD;
                $subtype = optional($value->fieldtype())->handle();
            } elseif ($value instanceof Entry) {
                $type = self::TYPE_ENTRY;
                $subtype = $value->collection()->handle();
            }
        } elseif ($type === self::TYPE_SET) {
            $subtype = $value['type'];
        } elseif ($type === self::TYPE_NODE || $type === self::TYPE_MARK) {
            $subtype = $value['type'];
        }

        $this->collection->push([
            'type' => $type,
            'subtype' => $subtype,
            'name' => Arr::last($path),
            'path' => $path ? implode('.', $path) : null,
            // 'parent' => $,
            // 'prev' => $,
            // 'next' => $,
            'value' => $value,
        ]);

        $continue = true;
        if (! $continue) {
            return false;
        }

        if ($type === self::TYPE_FIELD) {
            if ($subtype === self::SUBTYPE_REPLICATOR) {
                $continue = $this->collectReplicator($value, $path);
            } elseif ($subtype === self::SUBTYPE_BARD) {
                $continue = $this->collectBard($value, $path);
            } elseif ($subtype === self::SUBTYPE_GRID) {
                // $continue = $this->collectGrid($value, $path);
            }
        } if ($type === self::TYPE_ENTRY) {
            $continue = $this->collectEntry($value, $path);
        } elseif ($type === self::TYPE_SET) {
            $continue = $this->collectSet($value, $path);
        }

        return $continue;
    }

    protected function collectReplicator(Value $value, $path)
    {
        $data = $value->raw() ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $item = $data[$index];
            $set = $this->augmentSet($item, $value->fieldtype());
            $continue = $this->collect($set, array_merge($path, [$index]), self::TYPE_SET);
        }

        return $continue;
    }

    protected function collectBard(Value $value, $path)
    {
        return $this->collectBardNodes($value->raw() ?? [], $path, $value->fieldtype());
    }

    protected function collectBardNodes($nodes, $path, Bard $fieldtype)
    {
        $stack = array_keys($nodes);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $node = $nodes[$index];
            $continue = $node['type'] === 'set'
                ? $this->collectBardSet($node, array_merge($path, [$index]), $fieldtype)
                : $this->collectBardNode($node, array_merge($path, [$index]), $fieldtype);
        }

        return $continue;
    }

    protected function collectBardSet($set, $path, Bard $fieldtype)
    {
        $set = $this->augmentSet($set['attrs']['values'], $fieldtype);

        return $this->collect($set, $path, self::TYPE_SET);
    }

    protected function collectBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->collect($item, $path, self::TYPE_NODE);

        if ($continue) {
            $continue = $this->collectBardMarks($item['marks'] ?? [], array_merge($path, ['marks']), $fieldtype);
        }
        if ($continue) {
            $continue = $this->collectBardNodes($item['content'] ?? [], array_merge($path, ['content']), $fieldtype);
        }

        return $continue;
    }

    protected function collectBardMarks($marks, $path)
    {
        $stack = array_keys($marks);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $mark = $marks[$index];
            $continue = $this->collect($mark, array_merge($path, [$index]), self::TYPE_MARK);
        }

        return $continue;
    }

    protected function collectEntry(Entry $entry, $path)
    {
        $stack = $entry->blueprint()->fields()->all()->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $entry->augmentedValue($name);
            $continue = $this->collect($value, array_merge($path, [$name]));
        }

        return $continue;
    }

    protected function collectSet(array $set, $path)
    {
        $stack = array_keys(Arr::except($set, ['id', 'type', 'enabled']));

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $set[$name];
            $continue = $this->collect($value, array_merge($path, [$name]));
        }

        return $continue;
    }

    protected function augmentSet(array $set, Replicator|Bard $fieldtype)
    {
        return array_merge($set, $fieldtype
            ->fields($set['type'])
            ->addValues($set)
            ->shallowAugment()
            ->values()
            ->all());
    }
}
