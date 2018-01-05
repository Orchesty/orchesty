<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.1.18
 * Time: 10:12
 */

namespace Hanaboso\PipesFramework\Metrics\Builder;

use InfluxDB\Query\Builder as InfluxDbBuilder;
use InvalidArgumentException;

/**
 * Class Builder
 *
 * @package Metrics\Builder
 */
class Builder extends InfluxDbBuilder
{

    /**
     * @return string
     */
    protected function parseQuery(): string
    {
        if (!$this->metric) {
            throw new InvalidArgumentException('No metric provided to from()');
        }

        $metric = $this->metric;
        $rp     = '';

        if (is_string($this->retentionPolicy) && !empty($this->retentionPolicy)) {
            $rp     = sprintf('"%s".', $this->retentionPolicy);
            $metric = sprintf('"%s"', $metric);
        }

        $query = sprintf('SELECT %s FROM %s%s', $this->selection, $rp, $metric);

        for ($i = 0; $i < count($this->where); $i++) {
            $selection = 'WHERE';

            if ($i > 0) {
                $selection = 'AND';
            }

            $clause = $this->where[$i];
            $query  .= ' ' . $selection . ' ' . $clause;

        }

        if (!empty($this->groupBy)) {
            $query .= ' GROUP BY ' . implode(',', $this->groupBy);
        }

        if (!empty($this->orderBy)) {
            $query .= ' ORDER BY ' . implode(',', $this->orderBy);
        }

        if ($this->limitClause) {
            $query .= $this->limitClause;
        }

        return $query;
    }

}