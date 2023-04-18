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

    protected $includeSource;

    protected $depth;

    protected $minDepth;

    protected $maxDepth;

    public function __construct($params)
    {
        $this->parseParameters($params);
    }

    public function get()
    {
        return $this->results($this->query());
    }

    public function count()
    {
        return $this->query()->count();
    }

    protected function query()
    {
        $query = Distill::query($this->from);

        $this->queryType($query);
        $this->queryPath($query);
        $this->queryExpand($query);
        $this->queryDepth($query);
        $this->queryConditions($query);
        $this->queryStills($query);
        $this->queryOrderBys($query);

        return $query;
    }

    protected function queryType($query)
    {
        if (! isset($this->type)) {
            return;
        }

        $type = $this->type;
        if (! is_array($this->type)) {
            $type = explode('|', $this->type);
        }

        $query->type($type);
    }

    protected function queryPath($query)
    {
        if (! isset($this->path)) {
            return;
        }

        $path = $this->path;
        if (! is_array($this->path)) {
            $path = explode('|', $this->path);
        }

        $query->path($path);
    }

    protected function queryExpand($query)
    {
        if (! isset($this->expand)) {
            return;
        }

        $expand = $this->expand;
        if (! is_array($this->expand)) {
            $expand = explode('|', $this->expand);
        }

        $query->expand($expand);
    }

    protected function queryDepth($query)
    {
        if (isset($this->includeSource)) {
            $query->includeSource($this->includeSource);
        }
        if (isset($this->depth)) {
            $query->depth($this->depth);
        }
        if (isset($this->minDepth)) {
            $query->minDepth($this->minDepth);
        }
        if (isset($this->maxDepth)) {
            $query->maxDepth($this->maxDepth);
        }
    }

    public function queryStills($query)
    {
        $this->parseQueryStills()
            ->map(function ($handle) {
                return app('statamic.distill.stills')->get($handle);
            })
            ->filter()
            ->each(function ($class) use ($query) {
                $still = app($class);
                $still->apply($query, $this->params);
            });
    }

    protected function parseQueryStills()
    {
        $stills = $this->params->get('still');

        return collect(explode('|', $stills ?? ''));
    }

    protected function parseParameters($params)
    {
        $this->from = $params->get('from');
        $this->params = $params->except($this->ignoredParams);
        $this->orderBys = $this->parseOrderBys();
        $this->type = $this->params->get('type');
        $this->path = $this->params->get('path');
        $this->expand = $this->params->get('expand');
        $this->includeSource = $this->params->get('include_source');
        $this->depth = $this->params->get('depth');
        $this->minDepth = $this->params->get('minDepth');
        $this->maxDepth = $this->params->get('maxDepth');
    }
}
