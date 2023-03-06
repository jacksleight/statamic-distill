<?php

namespace JackSleight\StatamicDistill;

use Statamic\Contracts\Assets\Asset;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Fields\Value;
use Statamic\Fieldtypes\Bard;
use Statamic\Structures\Page;
use Statamic\Support\Arr;

class Walker
{
    protected $distiller;

    protected $value;

    public $stack = [];

    public $items = [];

    public function __construct(Distiller $distiller)
    {
        $this->distiller = $distiller;
    }

    public function walk($value)
    {
        $this->stack = [];
        $this->items = [];

        $this->walkValue($value);

        return $this->items;
    }

    protected function walkValue($value, $path = ['root'], $type = [])
    {
        if ($value instanceof Page) {
            $value = $value->entry();
        }

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
            } elseif ($value instanceof Value) {
                array_push($type,
                    Distiller::TYPE_FIELD,
                    optional($value->fieldtype())->handle()
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
        $this->stack[$self] = $item;
        if ($this->distiller->shouldInclude($item)) {
            if ($this->distiller->shouldContinue()) {
                $this->items[] = &$this->stack[$self];
            }
            if ($path) {
                $parent = implode('.', array_slice($path, 0, -1));
                if (isset($this->stack[$parent])) {
                    $this->stack[$self]['parent'] = &$this->stack[$parent];
                }
            }
            if ($path && in_array($type[0], [Distiller::TYPE_SET, Distiller::TYPE_ROW, Distiller::TYPE_NODE, Distiller::TYPE_MARK])) {
                $prev = implode('.', array_merge(array_slice($path, 0, -1), [Arr::last($path) - 1]));
                if (isset($this->stack[$prev])) {
                    $this->stack[$self]['prev'] = &$this->stack[$prev];
                    $this->stack[$prev]['next'] = &$this->stack[$self];
                }
            }
        }

        $continue = $this->distiller->shouldContinue(true);
        if (! $continue) {
            return false;
        }

        if ($type[0] === Distiller::TYPE_FIELD) {
            if ($type[1] === Distiller::FIELDTYPE_ENTRIES) {
                $continue = $this->walkEntries($value, $path);
            } elseif ($type[1] === Distiller::FIELDTYPE_TERMS) {
                $continue = $this->walkTerms($value, $path);
            } elseif ($type[1] === Distiller::FIELDTYPE_ASSETS) {
                $continue = $this->walkAssets($value, $path);
            } elseif ($type[1] === Distiller::FIELDTYPE_REPLICATOR) {
                $continue = $this->walkReplicator($value, $path);
            } elseif ($type[1] === Distiller::FIELDTYPE_BARD) {
                $continue = $this->walkBard($value, $path);
            } elseif ($type[1] === Distiller::FIELDTYPE_GRID) {
                $continue = $this->walkGrid($value, $path);
            }
        } elseif ($type[0] === Distiller::TYPE_ENTRY) {
            $continue = $this->walkEntry($value, $path);
        } elseif ($type[0] === Distiller::TYPE_TERM) {
            $continue = $this->walkTerm($value, $path);
        } elseif ($type[0] === Distiller::TYPE_ASSET) {
            $continue = $this->walkAsset($value, $path);
        } elseif ($type[0] === Distiller::TYPE_SET) {
            $continue = $this->walkSet($value, $path);
        } elseif ($type[0] === Distiller::TYPE_ROW) {
            $continue = $this->walkRow($value, $path);
        }

        return $continue;
    }

    protected function walkEntries(Value $value, $path)
    {
        return true;
    }

    protected function walkEntry(Entry $entry, $path)
    {
        $stack = $entry->blueprint()->fields()->all()->keys()->all();

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $entry->augmentedValue($name);
            $continue = $this->walkValue($value, array_merge($path, [$name]));
        }

        return $continue;
    }

    protected function walkTerms(Value $value, $path)
    {
        return true;
    }

    protected function walkTerm(Entry $term, $path)
    {
        return true;
    }

    protected function walkAssets(Value $value, $path)
    {
        $data = Arr::wrap($value->raw()) ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $item = $data[$index];
            $asset = $value->fieldtype()->augment($item);
            $continue = $this->walkValue($asset, array_merge($path, [$index]));
        }

        return $continue;
    }

    protected function walkAsset(Asset $asset, $path)
    {
        return true;
    }

    protected function walkReplicator(Value $value, $path)
    {
        $data = $value->raw() ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $item = $data[$index];
            $set = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->walkValue($set, array_merge($path, [$index]), [Distiller::TYPE_SET]);
        }

        return $continue;
    }

    protected function walkBard(Value $value, $path)
    {
        return $this->walkBardNodes($value->raw() ?? [], $path, $value->fieldtype());
    }

    protected function walkBardNodes($nodes, $path, Bard $fieldtype)
    {
        $stack = array_keys($nodes);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $node = $nodes[$index];
            $continue = $node['type'] === 'set'
                ? $this->walkBardSet($node, array_merge($path, [$index]), $fieldtype)
                : $this->walkBardNode($node, array_merge($path, [$index]), $fieldtype);
        }

        return $continue;
    }

    protected function walkBardSet($set, $path, Bard $fieldtype)
    {
        $set = $fieldtype->augment([$set])[0]->getProxiedInstance()->all();

        return $this->walkValue($set, $path, [Distiller::TYPE_SET]);
    }

    protected function walkBardNode($item, $path, Bard $fieldtype)
    {
        $continue = $this->walkValue($item, $path, [Distiller::TYPE_NODE]);

        if ($continue) {
            $continue = $this->walkBardMarks($item['marks'] ?? [], $path, $fieldtype);
        }
        if ($continue) {
            $continue = $this->walkBardNodes($item['content'] ?? [], $path, $fieldtype);
        }

        return $continue;
    }

    protected function walkBardMarks($marks, $path)
    {
        $stack = array_keys($marks);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $mark = $marks[$index];
            $continue = $this->walkValue($mark, array_merge($path, [$index]), [Distiller::TYPE_MARK]);
        }

        return $continue;
    }

    protected function walkGrid(Value $value, $path)
    {
        $data = $value->raw() ?? [];
        $stack = array_keys($data);

        $continue = true;
        while ($continue && count($stack)) {
            $index = array_shift($stack);
            $item = $data[$index];
            $row = $value->fieldtype()->augment([$item])[0]->getProxiedInstance()->all();
            $continue = $this->walkValue($row, array_merge($path, [$index]), [Distiller::TYPE_ROW]);
        }

        return $continue;
    }

    protected function walkSet(array $set, $path)
    {
        $stack = array_keys(Arr::except($set, ['id', 'type', 'enabled']));

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $set[$name];
            $continue = $this->walkValue($value, array_merge($path, [$name]));
        }

        return $continue;
    }

    protected function walkRow(array $row, $path)
    {
        $stack = array_keys(Arr::except($row, ['id']));

        $continue = true;
        while ($continue && count($stack)) {
            $name = array_shift($stack);
            $value = $row[$name];
            $continue = $this->walkValue($value, array_merge($path, [$name]));
        }

        return $continue;
    }
}
