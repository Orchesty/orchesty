<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb;

use Hanaboso\Utils\String\Json;

/**
 * Class Paging
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb
 */
final class Paging
{

    /**
     * Paging constructor.
     *
     * @param int|null $limit
     * @param int|null $offset
     */
    public function __construct(public ?int $limit = NULL, public ?int $offset = NULL)
    {
    }

    /**
     * @return string
     */
    public function toJson(): string
    {
        return Json::encode($this->toArray());
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $retArr = [];

        if ($this->limit) $retArr['limit']   = $this->limit;
        if ($this->offset) $retArr['offset'] = $this->offset;

        return $retArr;
    }

}
