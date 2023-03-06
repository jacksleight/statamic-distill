<?php

namespace JackSleight\StatamicDistill;

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

        return collect($this->items);
    }

    protected function collectValue($value, $path = [], $type = [])
    {
        $depth = count($path);

        if (! $type) {
            if ($value instanceof Entry) {
                array_push($type,
                    Distiller::TYPE_ENTRY,
                    $value->collection()->handle(),
                    $value->blueprint()->handle(),
                );
            } elseif ($value instanceof Term) {
                array_push($type,
                    Distiller::TYPE_TERM,
                    $value->taxonomy()->handle(),
                );
            } elseif ($value instanceof Asset) {
                array_push($type,
                    Distiller::TYPE_ASSET,
                    $value->container()->handle(),
                    $value->mime_type,
                );
            } elseif ($value instanceof User) {
                array_push($type,
                    Distiller::TYPE_USER,
                );
            } elseif ($value instanceof Value) {
                array_push($type,
                    Distiller::TYPE_FIELD,
                    optional($value->fieldtype())->handle() ?? 'unknown'
                );
            }
        } elseif (in_array($type[0], [Distiller::TYPE_SET])) {
            array_push($type,
                $value['type']
            );
        } elseif (in_array($type[0], [Distiller::TYPE_NODE, Distiller::TYPE_MARK])) {
            array_push($type,
                $value['type']
            );
        }

        if (! $type) {
            throw new \Exception('Unknown type');
        }

        $indexed = in_array($type[0], [
            Distiller::TYPE_SET,
            Distiller::TYPE_ROW,
            Distiller::TYPE_NODE,
            Distiller::TYPE_MARK,
        ]);

        $self = implode('.', $path);
        $item = [
            'type' => $type ? implode('::', $type) : null,
            'path' => $self,
            'name' => ! $indexed ? Arr::last($path) : null,
            'index' => $indexed ? Arr::last($path) : null,
            'parent' => null,
            'prev' => null,
            'next' => null,
            'data' => $value instanceof Value
                ? ['value' => $value]
                : $value,
        ];
        $this->store[$self] = $item;

        if ($this->distiller->shouldCollect($item, $depth)) {
            $this->items[] = &$this->store[$self];
            if ($path) {
                $parent = implode('.', array_slice($path, 0, -1));
                if (isset($this->store[$parent])) {
                    $this->store[$self]['parent'] = &$this->store[$parent];
                }
            }
            if ($path && $indexed) {
                $prev = implode('.', array_merge(array_slice($path, 0, -1), [Arr::last($path) - 1]));
                if (isset($this->store[$prev])) {
                    $this->store[$self]['prev'] = &$this->store[$prev];
                    $this->store[$prev]['next'] = &$this->store[$self];
                }
            }
        }

        $continue = $this->distiller->shouldContinue(count($this->items));
        if (! $continue) {
            return false;
        }

        if ($this->distiller->shouldExpand($item, $depth)) {
            if (in_array($type[0], [
                Distiller::TYPE_ENTRY,
                Distiller::TYPE_TERM,
                Distiller::TYPE_ASSET,
                Distiller::TYPE_USER,
            ]) && $depth === 0) {
                $continue = $this->collectObject($value, $path);
            } elseif ($type[0] === Distiller::TYPE_SET) {
                $continue = $this->collectSet($value, $path);
            } elseif ($type[0] === Distiller::TYPE_ROW) {
                $continue = $this->collectRow($value, $path);
            } elseif ($type[0] === Distiller::TYPE_FIELD) {
                if (in_array($type[1], [
                    Distiller::FIELDTYPE_ENTRIES,
                    Distiller::FIELDTYPE_TERMS,
                    Distiller::FIELDTYPE_ASSETS,
                    Distiller::FIELDTYPE_USERS,
                ])) {
                    $continue = $this->collectObjects($value, $path);
                } elseif ($type[1] === Distiller::FIELDTYPE_REPLICATOR) {
                    $continue = $this->collectReplicator($value, $path);
                } elseif ($type[1] === Distiller::FIELDTYPE_BARD) {
                    $continue = $this->collectBard($value, $path);
                } elseif ($type[1] === Distiller::FIELDTYPE_GRID) {
                    $continue = $this->collectGrid($value, $path);
                }
            }
        }

        return $continue;
    }

    protected function collectObject(Entry|Term|Asset|User $object, $path)
    {
        $stack = $object->blueprint()->fields()->all()->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $object->augmentedValue($name);
            $continue = $this->collectValue($value, array_merge($path, [$name]));
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
            $item = $data[$index];
            $object = $value->fieldtype()->augment($item);
            if ($object instanceof OrderedQueryBuilder) {
                $object = $object->first();
            }
            $continue = $this->collectValue($object, array_merge($path, [$index]));
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
            $set = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->collectValue($set, array_merge($path, [$index]), [Distiller::TYPE_SET]);
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
        $set = $fieldtype->augment([$set])[0]->getProxiedInstance()->all();

        return $this->collectValue($set, $path, [Distiller::TYPE_SET]);
    }

    protected function collectBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->collectValue($item, $path, [Distiller::TYPE_NODE]);

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
            $mark = $marks[$index];
            $continue = $this->collectValue($mark, array_merge($path, [$index]), [Distiller::TYPE_MARK]);
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
            $item = $data[$index];
            $row = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->collectValue($row, array_merge($path, [$index]), [Distiller::TYPE_ROW]);
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
            $continue = $this->collectValue($value, array_merge($path, [$name]));
        }

        return $continue;
    }

    protected function collectRow(array $row, $path)
    {
        $stack = array_keys(Arr::except($row, ['id']));

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $row[$name];
            $continue = $this->collectValue($value, array_merge($path, [$name]));
        }

        return $continue;
    }
}
