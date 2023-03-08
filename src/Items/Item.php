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

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function drop()
    {
        return $this->getSupplement('drop');
    }

    public function augmentedArrayData()
    {
        return array_merge($this->supplements(), $this->data);
    }

    public function getSearchReference(): string
    {
        return 'distill::'.$this->drop()->root->reference().'#'.$this->drop()->path;
    }

    public function getSearchValue(string $field)
    {
        return $this->data[$field];
    }

    public function toSearchResult(): ResultContract
    {
        return new Result($this, 'distill:'.$this->drop()->type);
    }
}
