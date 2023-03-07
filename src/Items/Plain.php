<?php

namespace JackSleight\StatamicDistill\Items;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\ContainsData;
use Statamic\Data\ContainsSupplementalData;
use Statamic\Data\HasAugmentedData;

class Plain implements Augmentable, Arrayable, ArrayAccess
{
    use ContainsData, ContainsSupplementalData, HasAugmentedData;

    public function __construct()
    {
        $this->data = collect();
        $this->supplements = collect();
    }

    public function augmentedArrayData()
    {
        return $this->data()->merge($this->supplements());
    }
}
