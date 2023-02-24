<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb;

use Hanaboso\Utils\String\Json;

/**
 * Class Filter
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb
 */
class Filter
{

    /**
     * Filter constructor.
     *
     * @param string[]|null $ids
     * @param bool|null     $deleted
     */
    public function __construct(public ?array $ids = NULL, public ?bool $deleted = NULL)
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
        $retArray = [];

        if ($this->ids) $retArray['ids']     = $this->ids;
        if ($this->ids) $retArray['deleted'] = $this->deleted;

        return $retArray;
    }

}
