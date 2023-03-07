<?php

namespace JackSleight\StatamicDistill\Items;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\ContainsData;
use Statamic\Data\HasAugmentedData;

class Still implements Augmentable, Arrayable, ArrayAccess
{
    use ContainsData, HasAugmentedData;

    public function __construct()
    {
        $this->data = collect();
    }
}
