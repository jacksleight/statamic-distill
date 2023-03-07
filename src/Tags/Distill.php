<?php

namespace JackSleight\StatamicDistill\Tags;

use JackSleight\StatamicDistill\Tags\Distill\Items;
use Statamic\Tags\Concerns;
use Statamic\Tags\Tags;

class Distill extends Tags
{
    use Concerns\OutputsItems;

    protected $defaultAsKey = 'items';

    public function wildcard(string $from)
    {
        $this->params->put('from', $from);

        return $this->index();
    }

    public function index()
    {
        $this->prepare();

        return $this->output($this->items()->get());
    }

    public function count()
    {
        $this->prepare();

        return $this->items()->count();
    }

    public function bard()
    {
        $this->prepare();

        $this->params->put('type', 'value:bard');

        return $this->items()->get()
            ->map->get('value')
            ->map->raw()
            ->flatten(1)
            ->all();
    }

    protected function items()
    {
        return new Items($this->params);
    }

    protected function prepare()
    {
        $this->params->put('from', $this->context->get($this->params->get('from')));
    }
}
