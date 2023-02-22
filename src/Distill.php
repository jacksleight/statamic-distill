<?php

namespace JackSleight\StatamicDistill;

use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Fieldtypes\Replicator;
use Statamic\Modifiers\Modifier;

class Distill extends Modifier
{
    public function index(Value $value)
    {
        return $this->traverse($value);
    }

    protected function traverse(Value $value)
    {
        $fieldtype = $value->fieldtype();

        if ($fieldtype instanceof Bard) {
            return $this->traverseBard($value, $fieldtype);
        } elseif ($fieldtype instanceof Replicator) {
            return $this->traverseReplicator($value, $fieldtype);
        }

        return [];
    }

    protected function traverseBard(Value $value, Bard $fieldtype)
    {
        $data = $value->raw() ?? [];

        $items = [];
        while (count($data)) {
            $items[] = $item = array_shift($data);
            if ($item['type'] === 'set') {
                array_push($items, ...$this->traverseSet($item['attrs']['values'], $fieldtype));
            } else {
                array_unshift($data, ...($item['marks'] ?? []));
                array_unshift($data, ...($item['content'] ?? []));
            }
        }

        return $items;
    }

    protected function traverseReplicator(Value $value, Replicator $fieldtype)
    {
        $data = $value->raw() ?? [];

        $items = [];
        while (count($data)) {
            $item = array_shift($data);
            // For return data consisteny convert to bard's set format
            $items[] = [
                'type' => 'set',
                'attrs' => ['values' => $item],
            ];
            array_push($items, ...$this->traverseSet($item, $fieldtype));
        }

        return $items;
    }

    protected function traverseSet(array $set, Replicator $fieldtype)
    {
        $values = $fieldtype->fields($set['type'])->addValues($set)->shallowAugment()->values()->all();

        $items = [];
        while (count($values)) {
            $value = array_shift($values);
            array_push($items, ...$this->traverse($value));
        }

        return $items;
    }
}
