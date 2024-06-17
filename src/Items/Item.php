<?php

namespace JackSleight\StatamicDistill\Items;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Search\Result as ResultContract;
use Statamic\Contracts\Search\Searchable as SearchableContract;
use Statamic\Data\ContainsSupplementalData;
use Statamic\Data\HasAugmentedData;
use Statamic\Search\Result;
use Statamic\Search\Searchable;

class Item implements Augmentable, ArrayAccess, Arrayable, SearchableContract
{
    use HasAugmentedData, ContainsSupplementalData, Searchable;

    protected $data;

    public static function placeholder($source, $path)
    {
        $item = new static([]);

        $item->setSupplement('info', new Info([
            'source' => $source,
            'path' => $path,
        ]));

        return $item;
    }

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function augmentedArrayData()
    {
        return array_merge($this->supplements(), $this->data);
    }

    public function getSearchReference(): string
    {
        return 'distill::'.$this->info->source->reference().'::'.$this->info->path;
    }

    public function getSearchValue(string $field)
    {
        return $this->data[$field] ?? null;
    }

    public function getCpSearchResultTitle()
    {
        return '';
    }

    public function getCpSearchResultBadge()
    {
        return '';
    }

    public function getCpSearchResultUrl()
    {
        return '';
    }

    public function toSearchResult(): ResultContract
    {
        return new Result($this, 'distill:'.$this->info->type);
    }

    // @todo Remove this when Statamic v4 is dropped and use augmentedValue instead
    public function internalValue($key)
    {
        return $this->data[$key] ?? null;
    }
}
