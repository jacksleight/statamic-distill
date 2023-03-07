<?php

namespace JackSleight\StatamicDistill\Tags\Distill;

use JackSleight\StatamicDistill\Facades\Distill;
use Statamic\Tags\Concerns;

class Items
{
    use Concerns\QueriesOrderBys,
        Concerns\GetsQueryResults;
    use Concerns\QueriesConditions {
        queryableConditionParams as traitQueryableConditionParams;
    }

    protected $ignoredParams = ['as'];

    protected $params;

    protected $from;

    protected $type;

    protected $path;

    protected $expand;

    protected $includeRoot;

    protected $depth;

    protected $minDepth;

    protected $maxDepth;

    public function __construct($params)
    {
        $this->parseParameters($params);
    }

    public function get()
    {
        return $this->results($this->distiller());
    }

    public function count()
    {
        return $this->distiller()->count();
    }

    protected function distiller()
    {
        $distiller = Distill::from($this->from);

        $this->queryType($distiller);
        $this->queryPath($distiller);
        $this->queryExpand($distiller);
        $this->queryDepth($distiller);
        $this->queryConditions($distiller);
        $this->queryOrderBys($distiller);

        return $distiller;
    }

    protected function queryType($distiller)
    {
        if (! isset($this->type)) {
            return;
        }

        $type = $this->type;
        if (! is_array($this->type)) {
            $type = explode('|', $this->type);
        }

        $distiller->type($type);
    }

    protected function queryPath($distiller)
    {
        if (! isset($this->path)) {
            return;
        }

        $path = $this->path;
        if (! is_array($this->path)) {
            $path = explode('|', $this->path);
        }

        $distiller->path($path);
    }

    protected function queryExpand($distiller)
    {
        if (! isset($this->expand)) {
            return;
        }

        $expand = $this->expand;
        if (! is_array($this->expand)) {
            $expand = explode('|', $this->expand);
        }

        $distiller->expand($expand);
    }

    protected function queryDepth($distiller)
    {
        if (isset($this->includeRoot)) {
            $distiller->includeRoot($this->includeRoot);
        }
        if (isset($this->depth)) {
            $distiller->depth($this->depth);
        }
        if (isset($this->minDepth)) {
            $distiller->minDepth($this->minDepth);
        }
        if (isset($this->maxDepth)) {
            $distiller->maxDepth($this->maxDepth);
        }
    }

    protected function parseParameters($params)
    {
        $this->from = $params->get('from');
        $this->params = $params->except($this->ignoredParams);
        $this->orderBys = $this->parseOrderBys();
        $this->type = $this->params->get('type');
        $this->path = $this->params->get('path');
        $this->expand = $this->params->get('expand');
        $this->includeRoot = $this->params->get('includeRoot');
        $this->depth = $this->params->get('depth');
        $this->minDepth = $this->params->get('minDepth');
        $this->maxDepth = $this->params->get('maxDepth');
    }
}
