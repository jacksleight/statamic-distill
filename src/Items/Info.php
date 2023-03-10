<?php

namespace JackSleight\StatamicDistill\Items;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Data\HasAugmentedData;

class Info implements Augmentable, ArrayAccess, Arrayable
{
    use HasAugmentedData;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function augmentedArrayData()
    {
        return $this->data;
    }

    public function setParent(Item $item)
    {
        return $this->data['parent'] = $item;
    }

    public function setPrev(Item $item)
    {
        return $this->data['prev'] = $item;
    }

    public function setNext(Item $item)
    {
        return $this->data['next'] = $item;
    }
}
