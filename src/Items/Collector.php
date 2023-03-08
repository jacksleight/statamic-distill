<?php

namespace JackSleight\StatamicDistill\Items;

use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Query\OrderedQueryBuilder;
use Statamic\Structures\Page;
use Statamic\Support\Arr;

class Collector
{
    protected $distiller;

    protected $value;

    public $store = [];

    public $items = [];

    public function __construct(Distiller $distiller)
    {
        $this->distiller = $distiller;
    }

    public function collect($value)
    {
        if ($value instanceof Page) {
            $value = $value->entry();
        }

        $this->store = [];
        $this->items = [];

        $this->collectValue($value);

        return $this->items;
    }

    protected function collectValue($value, $path = [], $type = null)
    {
        $depth = count($path);

        if (! $type) {
            if ($value instanceof Entry) {
                $type = Distiller::TYPE_ENTRY;
            } elseif ($value instanceof Term) {
                $type = Distiller::TYPE_TERM;
            } elseif ($value instanceof Asset) {
                $type = Distiller::TYPE_ASSET;
            } elseif ($value instanceof User) {
                $type = Distiller::TYPE_USER;
            } elseif ($value instanceof Value) {
                $type = Distiller::TYPE_VALUE.':'.optional($value->fieldtype())->handle() ?? 'unknown';
            }
        }

        $primary = $type;

        if (in_array($type, [Distiller::TYPE_SET])) {
            $type .= ':'.$value['type'];
        } elseif (in_array($type, [Distiller::TYPE_NODE, Distiller::TYPE_MARK])) {
            $type .= ':'.$value['type'];
        }

        if (! $type) {
            throw new \Exception('Unknown type');
        }

        $indexed = in_array($type, [
            Distiller::TYPE_SET,
            Distiller::TYPE_ROW,
            Distiller::TYPE_NODE,
            Distiller::TYPE_MARK,
        ]);

        $self = implode('.', $path);
        $item = $this->createItem($value, [
            'type' => $type,
            'path' => $self,
            'name' => ! $indexed ? Arr::last($path) : null,
            'index' => $indexed ? Arr::last($path) : null,
            'parent' => null,
            'prev' => null,
            'next' => null,
        ]);
        $this->store[$self] = $item;

        if ($this->distiller->shouldCollect($item, $depth)) {
            $this->items[] = $item;
            if ($path) {
                $parent = implode('.', array_slice($path, 0, -1));
                if (isset($this->store[$parent])) {
                    $this->store[$self]->drop->setParent($this->store[$parent]);
                }
            }
            if ($path && $indexed) {
                $prev = implode('.', array_merge(array_slice($path, 0, -1), [Arr::last($path) - 1]));
                if (isset($this->store[$prev])) {
                    $this->store[$self]->drop->setPrev($this->store[$prev]);
                    $this->store[$prev]->drop->setNext($this->store[$self]);
                }
            }
        }

        $continue = $this->distiller->shouldContinue(count($this->items));
        if (! $continue) {
            return false;
        }

        if ($depth === 0 || $this->distiller->shouldExpand($item, $depth)) {
            if (in_array($primary, [
                Distiller::TYPE_ENTRY,
                Distiller::TYPE_TERM,
                Distiller::TYPE_ASSET,
                Distiller::TYPE_USER,
            ]) && $depth === 0) {
                $continue = $this->collectObject($value, $path);
            } elseif (in_array($type, [
                Distiller::TYPE_VALUE_ENTRIES,
                Distiller::TYPE_VALUE_TERMS,
                Distiller::TYPE_VALUE_ASSETS,
                Distiller::TYPE_VALUE_USERS,
            ])) {
                $continue = $this->collectObjects($value, $path);
            } elseif ($type === Distiller::TYPE_VALUE_REPLICATOR) {
                $continue = $this->collectReplicator($value, $path);
            } elseif ($type === Distiller::TYPE_VALUE_BARD) {
                $continue = $this->collectBard($value, $path);
            } elseif ($type === Distiller::TYPE_VALUE_GRID) {
                $continue = $this->collectGrid($value, $path);
            } elseif ($primary === Distiller::TYPE_SET) {
                $continue = $this->collectSet($value, $path);
            } elseif ($primary === Distiller::TYPE_ROW) {
                $continue = $this->collectRow($value, $path);
            }
        }

        return $continue;
    }

    protected function createItem($value, $drop)
    {
        if ($value instanceof Value) {
            $value = ['value' => $value];
        }

        if (is_array($value)) {
            $value = new Item($value);
        }

        $drop = new Drop($drop);
        $value->setSupplement('drop', $drop);

        return $value;
    }

    protected function collectObject(Entry|Term|Asset|User $object, $path)
    {
        $stack = $object->blueprint()->fields()->all()->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $current = array_merge($path, [$name]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $value = $object->augmentedValue($name);
            $continue = $this->collectValue($value, $current);
        }

        return $continue;
    }

    protected function collectObjects(Value $value, $path)
    {
        $data = Arr::wrap($value->raw()) ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $current = array_merge($path, [$index]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            $object = $value->fieldtype()->augment($item);
            if ($object instanceof OrderedQueryBuilder) {
                $object = $object->first();
            }
            $continue = $this->collectValue($object, $current);
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
            $current = array_merge($path, [$index]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            $set = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->collectValue($set, $current, Distiller::TYPE_SET);
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
            $current = array_merge($path, [$index]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $node = $nodes[$index];
            $continue = $node['type'] === 'set'
                ? $this->collectBardSet($node, $current, $fieldtype)
                : $this->collectBardNode($node, $current, $fieldtype);
        }

        return $continue;
    }

    protected function collectBardSet($set, $path, Bard $fieldtype)
    {
        $set = $fieldtype->augment([$set])[0]->getProxiedInstance()->all();

        return $this->collectValue($set, $path, Distiller::TYPE_SET);
    }

    protected function collectBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->collectValue($item, $path, Distiller::TYPE_NODE);

        if ($continue) {
            $continue = $this->collectBardMarks($item['marks'] ?? [], $path, $fieldtype);
        }
        if ($continue) {
            $continue = $this->collectBardNodes($item['content'] ?? [], $path, $fieldtype);
        }

        return $continue;
    }

    protected function collectBardMarks($marks, $path)
    {
        $stack = array_keys($marks);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $current = array_merge($path, [$index]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $mark = $marks[$index];
            $continue = $this->collectValue($mark, $current, Distiller::TYPE_MARK);
        }

        return $continue;
    }

    protected function collectGrid(Value $value, $path)
    {
        $data = $value->raw() ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $current = array_merge($path, [$index]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            $row = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->collectValue($row, $current, Distiller::TYPE_ROW);
        }

        return $continue;
    }

    protected function collectSet(array $set, $path)
    {
        $stack = array_keys(Arr::except($set, ['id', 'type', 'enabled']));

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $current = array_merge($path, [$name]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $value = $set[$name];
            $continue = $this->collectValue($value, $current);
        }

        return $continue;
    }

    protected function collectRow(array $row, $path)
    {
        $stack = array_keys(Arr::except($row, ['id']));

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $current = array_merge($path, [$name]);
            if (! $this->distiller->shouldTraverse($current)) {
                continue;
            }
            $value = $row[$name];
            $continue = $this->collectValue($value, $current);
        }

        return $continue;
    }
}
