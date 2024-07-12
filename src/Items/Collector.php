<?php

namespace JackSleight\StatamicDistill\Items;

use Exception;
use Illuminate\Support\Collection;
use JackSleight\StatamicDistill\Distill;
use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Query\Builder;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;
use Statamic\Fields\Values;
use Statamic\Fieldtypes\Bard;
use Statamic\Structures\Page;
use Statamic\Support\Arr;
use Statamic\Support\Str;
use stdClass;

class Collector
{
    protected $query;

    protected $value;

    public $index = [];

    public $store = [];

    public $items = [];

    public function __construct(QueryBuilder $query)
    {
        $this->query = $query;
    }

    public function collect($value)
    {
        if ($value instanceof Page) {
            $value = $value->entry();
        }
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        $this->value = $value;

        $this->index = [];
        $this->store = [];
        $this->items = [];

        $this->collectValue($this->value);

        return $this->items;
    }

    protected function collectValue($value, $path = [], $type = null)
    {
        if ($value instanceof Values) {
            $value = $value->getProxiedInstance();
        }
        if ($value instanceof Collection) {
            $value = $value->all();
        }

        $depth = count($path);

        if (! $type) {
            if ($value instanceof Entry) {
                $type = Distill::TYPE_ENTRY.':'.$value->collection()->handle();
            } elseif ($value instanceof Term) {
                $type = Distill::TYPE_TERM.':'.$value->taxonomy()->handle();
            } elseif ($value instanceof Asset) {
                $type = Distill::TYPE_ASSET.':'.$value->container()->handle();
            } elseif ($value instanceof User) {
                $type = Distill::TYPE_USER;
            } elseif ($value instanceof Value) {
                $type = Distill::TYPE_VALUE.':'.optional($value->fieldtype())->handle() ?? 'unknown';
            } else {
                $raw = Str::slug(gettype($value));
                $type = Distill::TYPE_RAW.':'.$raw;
                if (! in_array($type, [
                    Distill::TYPE_RAW_ARRAY,
                    Distill::TYPE_RAW_BOOLEAN,
                    Distill::TYPE_RAW_FLOAT,
                    Distill::TYPE_RAW_INTEGER,
                    Distill::TYPE_RAW_NULL,
                    Distill::TYPE_RAW_OBJECT,
                    Distill::TYPE_RAW_STRING,
                ])) {
                    throw new Exception('Unsupported raw type: '.$raw);
                } elseif ($type === Distill::TYPE_RAW_OBJECT && ! $value instanceof stdClass) {
                    throw new Exception('Unsupported object type: '.get_class($value));
                }
            }
        }

        $primary = Str::before($type, ':');
        $self = implode('.', $path);
        $info = [
            'type' => $type,
            'path' => $self,
            // 'name' => ! $indexed ? Arr::last($path) : null,
            // 'index' => $indexed ? Arr::last($path) : null,
            'source' => &$this->value,
            'parent' => null,
            'signature' => $this->generateSignature($primary, $value),
            // 'prev' => null,
            // 'next' => null,
        ];

        $item = $value;

        if ($primary === Distill::TYPE_VALUE || in_array($type, [
            Distill::TYPE_RAW_BOOLEAN,
            Distill::TYPE_RAW_INTEGER,
            Distill::TYPE_RAW_FLOAT,
            Distill::TYPE_RAW_STRING,
            Distill::TYPE_RAW_NULL,
        ])) {
            $item = ['value' => $item];
        }
        if ($type === Distill::TYPE_RAW_OBJECT) {
            $item = get_object_vars($item);
        }
        if (is_array($item)) {
            $item = new Item($item);
        }

        $item->setSupplement('info', new Info($info));

        $this->store[$self] = $item;

        if ($this->query->shouldCollect($item, $depth, $this->index)) {
            $this->items[] = $item;
            $this->index[] = $item->info->signature;
            if (isset($dtid)) {
            }
            if ($path) {
                $parent = implode('.', array_slice($path, 0, -1));
                if (isset($this->store[$parent])) {
                    $this->store[$self]->info->setParent($this->store[$parent]);
                }
            }
            // if ($path && $indexed) {
            //     $prev = implode('.', array_merge(array_slice($path, 0, -1), [Arr::last($path) - 1]));
            //     if (isset($this->store[$prev])) {
            //         $this->store[$self]->info->setPrev($this->store[$prev]);
            //         $this->store[$prev]->info->setNext($this->store[$self]);
            //     }
            // }
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
                $continue = $this->collectRelationship($value, $path);
            } elseif (in_array($type, [
                Distill::TYPE_VALUE_ENTRIES,
                Distill::TYPE_VALUE_TERMS,
                Distill::TYPE_VALUE_ASSETS,
                Distill::TYPE_VALUE_USERS,
            ])) {
                $continue = $this->collectRelationships($value, $path);
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
            } elseif ($type === Distill::TYPE_RAW_ARRAY) {
                $continue = $this->collectRawArray($value, $path);
            } elseif ($type === Distill::TYPE_RAW_OBJECT) {
                $continue = $this->collectRawObject($value, $path);
            }
        }

        return $continue;
    }

    protected function collectRelationship(Entry|Term|Asset|User $object, $path)
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

    protected function collectRelationships(Value $value, $path)
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
            if ($object instanceof Builder) {
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
            if (! Arr::get($item, 'enabled', true)) {
                continue;
            }
            if (empty($augmentedValue = $value->fieldtype()->augment([$item]))) {
                continue;
            }
            $set = $augmentedValue[0];
            if (is_array($set)) {
                continue;
            }
            $set = $set->getProxiedInstance()->all();
            $continue = $this->collectValue($set, $current, Distill::TYPE_SET.':'.$set['type']);
        }

        return $continue;
    }

    protected function collectBard(Value $value, $path)
    {
        $raw = $value->raw();
        if (is_string($raw)) {
            return true;
        }

        return $this->collectBardNodes($raw ?? [], $path, $value->fieldtype());
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
                if (! Arr::get($node, 'enabled', true)) {
                    continue;
                }
                if (empty($augmentedValue = $fieldtype->augment([$node]))) {
                    continue;
                }
                $set = $augmentedValue[0];
                if (is_array($set)) {
                    continue;
                }
                $set = $set->getProxiedInstance()->all();
                $continue = $this->collectValue($set, $path, Distill::TYPE_SET.':'.$set['type']);
            } else {
                $continue = $this->collectBardNode($node, $current, $fieldtype);
            }
        }

        return $continue;
    }

    protected function collectBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->collectValue($item, $path, Distill::TYPE_NODE.':'.$item['type']);

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
            $continue = $this->collectValue($mark, $current, Distill::TYPE_MARK).':'.$mark['type'];
        }

        return $continue;
    }

    protected function collectGrid(Value $value, $path)
    {
        $type = $value->field()->get('dtl_type', 'unknown');

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
            $row = $value->fieldtype()->augment([$item])[0];
            if (is_array($row)) {
                continue;
            }
            $row = $row->getProxiedInstance()->all();
            $continue = $this->collectValue($row, $current, Distill::TYPE_ROW.':'.$type);
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

    protected function collectRawArray(array $value, $path)
    {
        $data = $value;
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $current = array_merge($path, [$index]);
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            $continue = $this->collectValue($item, $current);
        }

        return $continue;
    }

    protected function collectRawObject(stdClass $value, $path)
    {
        $data = get_object_vars($value);
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $current = array_merge($path, [$index]);
            if (! $this->query->shouldTraverse($current)) {
                continue;
            }
            $item = $data[$index];
            $continue = $this->collectValue($item, $current);
        }

        return $continue;
    }

    protected function generateSignature($primary, $value)
    {
        if (in_array($primary, [
            Distill::TYPE_ENTRY,
            Distill::TYPE_TERM,
            Distill::TYPE_ASSET,
            Distill::TYPE_USER,
        ])) {
            $reference = $value->reference();
        } else {
            $reference = uniqid();
        }

        return md5($reference);
    }
}
