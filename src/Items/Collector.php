<?php

namespace JackSleight\StatamicDistill\Items;

use JackSleight\StatamicDistill\Distill;
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
    protected $query;

    protected $value;

    public $store = [];

    public $items = [];

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    public function collect(Entry|Term|Asset|User|Value $value)
    {
        if ($value instanceof Page) {
            $value = $value->entry();
        }

        $this->value = $value;

        $this->store = [];
        $this->items = [];

        $this->collectValue($this->value);

        return $this->items;
    }

    protected function collectValue($value, $path = [], $type = null)
    {
        $depth = count($path);

        if (! $type) {
            if ($value instanceof Entry) {
                $type = Distill::TYPE_ENTRY;
            } elseif ($value instanceof Term) {
                $type = Distill::TYPE_TERM;
            } elseif ($value instanceof Asset) {
                $type = Distill::TYPE_ASSET;
            } elseif ($value instanceof User) {
                $type = Distill::TYPE_USER;
            } elseif ($value instanceof Value) {
                $type = Distill::TYPE_VALUE.':'.optional($value->fieldtype())->handle() ?? 'unknown';
            }
        }

        $primary = $type;

        if (in_array($type, [Distill::TYPE_SET])) {
            $type .= ':'.$value['type'];
        } elseif (in_array($type, [Distill::TYPE_ROW])) {
            $type .= ':unknown';
        } elseif (in_array($type, [Distill::TYPE_NODE, Distill::TYPE_MARK])) {
            $type .= ':'.$value['type'];
        }

        if (! $type) {
            throw new \Exception('Value is an unknown type');
        }

        $indexed = in_array($type, [
            Distill::TYPE_SET,
            Distill::TYPE_ROW,
            Distill::TYPE_NODE,
            Distill::TYPE_MARK,
        ]);

        $self = implode('.', $path);
        $item = $this->createItem($value, [
            'type' => $type,
            'path' => $self,
            'name' => ! $indexed ? Arr::last($path) : null,
            'index' => $indexed ? Arr::last($path) : null,
            'source' => &$this->value,
            'parent' => null,
            'prev' => null,
            'next' => null,
        ]);
        $this->store[$self] = $item;

        if ($this->query->shouldCollect($item, $depth)) {
            $this->items[] = $item;
            if ($path) {
                $parent = implode('.', array_slice($path, 0, -1));
                if (isset($this->store[$parent])) {
                    $this->store[$self]->info->setParent($this->store[$parent]);
                }
            }
            if ($path && $indexed) {
                $prev = implode('.', array_merge(array_slice($path, 0, -1), [Arr::last($path) - 1]));
                if (isset($this->store[$prev])) {
                    $this->store[$self]->info->setPrev($this->store[$prev]);
                    $this->store[$prev]->info->setNext($this->store[$self]);
                }
            }
        }

        $continue = $this->query->shouldContinue(count($this->items));
        if (! $continue) {
            return false;
        }

        if ($depth === 0 || $this->query->shouldExpand($item, $depth)) {
            if (in_array($primary, [
                Distill::TYPE_ENTRY,
                Distill::TYPE_TERM,
                Distill::TYPE_ASSET,
                Distill::TYPE_USER,
            ]) && $depth === 0) {
                $continue = $this->collectObject($value, $path);
            } elseif (in_array($type, [
                Distill::TYPE_VALUE_ENTRIES,
                Distill::TYPE_VALUE_TERMS,
                Distill::TYPE_VALUE_ASSETS,
                Distill::TYPE_VALUE_USERS,
            ])) {
                $continue = $this->collectObjects($value, $path);
            } elseif ($type === Distill::TYPE_VALUE_REPLICATOR) {
                $continue = $this->collectReplicator($value, $path);
            } elseif ($type === Distill::TYPE_VALUE_BARD) {
                $continue = $this->collectBard($value, $path);
            } elseif ($type === Distill::TYPE_VALUE_GRID) {
                $continue = $this->collectGrid($value, $path);
            } elseif ($primary === Distill::TYPE_SET) {
                $continue = $this->collectSet($value, $path);
            } elseif ($primary === Distill::TYPE_ROW) {
                $continue = $this->collectRow($value, $path);
            }
        }

        return $continue;
    }

    protected function createItem($value, $info)
    {
        if ($value instanceof Value) {
            $value = ['value' => $value];
        }

        if (is_array($value)) {
            $value = new Item($value);
        }

        $value->setSupplement('is_distilled', true);
        $value->setSupplement('info', new Info($info));

        return $value;
    }

    protected function collectObject(Entry|Term|Asset|User $object, $path)
    {
        $stack = $object->blueprint()->fields()->all()->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $current = array_merge($path, [$name]);
            if (! $this->query->shouldTraverse($current)) {
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
            if (! $this->query->shouldTraverse($current)) {
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
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            if (! $item['enabled']) {
                continue;
            }
            $set = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->collectValue($set, $current, Distill::TYPE_SET);
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
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $node = $nodes[$index];
            if ($node['type'] === 'set') {
                if (! ($node['enabled'] ?? true)) {
                    continue;
                }
                $continue = $this->collectBardSet($node, $current, $fieldtype);
            } else {
                $continue = $this->collectBardNode($node, $current, $fieldtype);
            }
        }

        return $continue;
    }

    protected function collectBardSet($set, $path, Bard $fieldtype)
    {
        $set = $fieldtype->augment([$set])[0]->getProxiedInstance()->all();

        return $this->collectValue($set, $path, Distill::TYPE_SET);
    }

    protected function collectBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->collectValue($item, $path, Distill::TYPE_NODE);

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
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $mark = $marks[$index];
            $continue = $this->collectValue($mark, $current, Distill::TYPE_MARK);
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
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            $row = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->collectValue($row, $current, Distill::TYPE_ROW);
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
            if (! $this->query->shouldTraverse($current)) {
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
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $value = $row[$name];
            $continue = $this->collectValue($value, $current);
        }

        return $continue;
    }
}
