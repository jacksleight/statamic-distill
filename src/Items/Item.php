<?php

namespace JackSleight\StatamicDistill\Items;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\ContainsSupplementalData;
use Statamic\Data\HasAugmentedData;

class Item implements Augmentable, ArrayAccess, Arrayable
{
    use HasAugmentedData, ContainsSupplementalData;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function augmentedArrayData()
    {
        return array_merge($this->supplements(), $this->data);
    }
}
