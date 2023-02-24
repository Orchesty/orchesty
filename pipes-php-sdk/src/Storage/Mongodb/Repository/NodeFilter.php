<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository;

use Hanaboso\PipesPhpSdk\Storage\Mongodb\Filter;

/**
 * Class NodeFilter
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb\Repository
 */
final class NodeFilter extends Filter
{

    /**
     * NodeFilter constructor.
     *
     * @param string[]|null $topologies
     * @param string[]|null $ids
     * @param bool|null     $deleted
     */
    public function __construct(public ?array $topologies = NULL, ?array $ids = NULL, ?bool $deleted = NULL,)
    {
        parent::__construct($ids, $deleted);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $retArray = parent::toArray();

        if ($this->topologies) $retArray['topologies'] = $this->topologies;

        return $retArray;
    }

}
