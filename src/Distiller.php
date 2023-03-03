<?php

namespace JackSleight\StatamicDistill;

use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;
use Statamic\Fields\Values;
use Statamic\Fieldtypes\Bard;
use Statamic\Structures\Page;
use Statamic\Support\Arr;
use Statamic\Support\Str;

class Distiller
{
    const TYPE_ENTRY = 'entry';

    const TYPE_TERM = 'term';

    const TYPE_ASSET = 'asset';

    const TYPE_FIELD = 'field';

    const TYPE_SET = 'set';

    const TYPE_ROW = 'row';

    const TYPE_NODE = 'node';

    const TYPE_MARK = 'mark';

    const FIELDTYPE_ENTRIES = 'entries';

    const FIELDTYPE_TERMS = 'terms';

    const FIELDTYPE_ASSETS = 'assets';

    const FIELDTYPE_REPLICATOR = 'replicator';

    const FIELDTYPE_BARD = 'bard';

    const FIELDTYPE_GRID = 'grid';

    protected $value;

    protected $criteria = [];

    protected $limit;

    protected $found = [];

    protected $result = [];

    public function from($value)
    {
        $this->value = $value;

        return $this;
    }

    public function type($type)
    {
        $this->criteria[] = ['type', '__type', $type];

        return $this;
    }

    public function where($name, $operator, $value)
    {
        $this->criteria[] = ["value.{$name}", $operator, $value];

        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    public function get()
    {
        $this->found = [];
        $this->result = [];

        $this->collect($this->value);

        return collect($this->result);
    }

    protected function collect($value, $path = ['root'], $type = [])
    {
        if ($value instanceof Page) {
            $value = $value->entry();
        }

        if (! $type) {
            if ($value instanceof Entry) {
                array_push($type,
                    self::TYPE_ENTRY,
                    $value->collection()->handle(),
                    $value->blueprint()->handle(),
                );
            } elseif ($value instanceof Term) {
                array_push($type,
                    self::TYPE_TERM,
                    $value->taxonomy()->handle(),
                );
            } elseif ($value instanceof Asset) {
                array_push($type,
                    self::TYPE_ASSET,
                    $value->container()->handle(),
                    $value->mime_type,
                );
            } elseif ($value instanceof Value) {
                array_push($type,
                    self::TYPE_FIELD,
                    optional($value->fieldtype())->handle()
                );
            }
        } elseif (in_array($type[0], [self::TYPE_SET])) {
            array_push($type,
                $value['type']
            );
        } elseif (in_array($type[0], [self::TYPE_NODE, self::TYPE_MARK])) {
            array_push($type,
                $value['type']
            );
        }

        $self = implode('.', $path);
        $item = [
            'type' => $type ? implode('::', $type) : null,
            'path' => $self,
            'name' => Arr::last($path),
            'parent' => null,
            'prev' => null,
            'next' => null,
            'value' => $value,
        ];
        $this->found[$self] = $item;
        if ($this->checkCriteria($item)) {
            if ($this->checkLimit()) {
                $this->result[] = &$this->found[$self];
            }
            if ($path) {
                $parent = implode('.', array_slice($path, 0, -1));
                if (isset($this->found[$parent])) {
                    $this->found[$self]['parent'] = &$this->found[$parent];
                }
            }
            if ($path && in_array($type[0], [self::TYPE_SET, self::TYPE_ROW, self::TYPE_NODE, self::TYPE_MARK])) {
                $prev = implode('.', array_merge(array_slice($path, 0, -1), [Arr::last($path) - 1]));
                if (isset($this->found[$prev])) {
                    $this->found[$self]['prev'] = &$this->found[$prev];
                    $this->found[$prev]['next'] = &$this->found[$self];
                }
            }
        }

        $continue = $this->checkLimit(true);
        if (! $continue) {
            return false;
        }

        if ($type[0] === self::TYPE_FIELD) {
            if ($type[1] === self::FIELDTYPE_ENTRIES) {
                $continue = $this->collectEntries($value, $path);
            } elseif ($type[1] === self::FIELDTYPE_TERMS) {
                $continue = $this->collectTerms($value, $path);
            } elseif ($type[1] === self::FIELDTYPE_ASSETS) {
                $continue = $this->collectAssets($value, $path);
            } elseif ($type[1] === self::FIELDTYPE_REPLICATOR) {
                $continue = $this->collectReplicator($value, $path);
            } elseif ($type[1] === self::FIELDTYPE_BARD) {
                $continue = $this->collectBard($value, $path);
            } elseif ($type[1] === self::FIELDTYPE_GRID) {
                $continue = $this->collectGrid($value, $path);
            }
        } elseif ($type[0] === self::TYPE_ENTRY) {
            $continue = $this->collectEntry($value, $path);
        } elseif ($type[0] === self::TYPE_TERM) {
            $continue = $this->collectTerm($value, $path);
        } elseif ($type[0] === self::TYPE_ASSET) {
            $continue = $this->collectAsset($value, $path);
        } elseif ($type[0] === self::TYPE_SET) {
            $continue = $this->collectSet($value, $path);
        } elseif ($type[0] === self::TYPE_ROW) {
            $continue = $this->collectRow($value, $path);
        }

        return $continue;
    }

    protected function checkCriteria($item)
    {
        foreach ($this->criteria as [$name, $operator, $checks]) {
            $value = data_get($item, $name);
            if ($operator === '=') {
                if ($value !== $checks) {
                    return false;
                }
            } elseif ($operator === '__type') {
                $checks = collect(explode('|', $checks))
                    ->map(fn ($type) => ! Str::endsWith($type, '::*') ? "{$type}::*" : $type)
                    ->all();
                foreach ($checks as $check) {
                    if (Str::is($check, "{$value}::")) {
                        continue 2;
                    }
                }

                return false;
            }
        }

        return true;
    }

    protected function checkLimit($offset = false)
    {
        if (! isset($this->limit)) {
            return true;
        }

        return count($this->result) < ($this->limit + ($offset ? 1 : 0));
    }

    protected function collectEntries(Value $value, $path)
    {
        return true;
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

    protected function collectTerms(Value $value, $path)
    {
        return true;
    }

    protected function collectTerm(Entry $term, $path)
    {
        return true;
    }

    protected function collectAssets(Value $value, $path)
    {
        $data = Arr::wrap($value->raw()) ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $item = $data[$index];
            $asset = $value->fieldtype()->augment($item);
            $continue = $this->collect($asset, array_merge($path, [$index]));
        }

        return $continue;
    }

    protected function collectAsset(Asset $asset, $path)
    {
        return true;
    }

    protected function collectReplicator(Value $value, $path)
    {
        $data = $value->raw() ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $item = $data[$index];
            $set = $value->fieldtype()->augment([$item])[0];
            $continue = $this->collect($set, array_merge($path, [$index]), [self::TYPE_SET]);
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
        $set = $fieldtype->augment([$set])[0];

        return $this->collect($set, $path, [self::TYPE_SET]);
    }

    protected function collectBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->collect($item, $path, [self::TYPE_NODE]);

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
            $continue = $this->collect($mark, array_merge($path, [$index]), [self::TYPE_MARK]);
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
            $row = $value->fieldtype()->augment([$item])[0];
            $continue = $this->collect($row, array_merge($path, [$index]), [self::TYPE_ROW]);
        }

        return $continue;
    }

    protected function collectSet(Values $set, $path)
    {
        $stack = $set->getProxiedInstance()->except(['id', 'type', 'enabled'])->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $set->getProxiedInstance()->get($name);
            $continue = $this->collect($value, array_merge($path, [$name]));
        }

        return $continue;
    }

    protected function collectRow(Values $row, $path)
    {
        $stack = $row->getProxiedInstance()->except(['id'])->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $row->getProxiedInstance()->get($name);
            $continue = $this->collect($value, array_merge($path, [$name]));
        }

        return $continue;
    }
}
