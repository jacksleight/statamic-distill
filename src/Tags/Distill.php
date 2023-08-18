<?php

namespace JackSleight\StatamicDistill\Tags;

use JackSleight\StatamicDistill\Facades\Distill as Facade;
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
        $this->params->put('include_source', true);

        $items = $this->items()->get();

        return Facade::extractBard($items);
    }

    public function text()
    {
        $this->prepare();

        $this->params->put('type', [
            'value:text',
            'value:textarea',
            'value:bard',
            'value:markdown',
        ]);
        $this->params->put('include_source', true);

        $items = $this->items()->get();

        return Facade::extractText($items);
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
