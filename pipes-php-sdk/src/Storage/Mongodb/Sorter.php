<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Storage\Mongodb;

use Hanaboso\Utils\String\Json;

/**
 * Class Sorter
 *
 * @package Hanaboso\PipesPhpSdk\Storage\Mongodb
 */
final class Sorter
{

    /**
     * Sorter constructor.
     *
     * @param SortDirection|null $created
     */
    public function __construct(public ?SortDirection $created = NULL)
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
     * @return mixed[]|null
     */
    public function toArray(): ?array
    {
        if ($this->created) {
            return [
                'created'     => $this->created,
            ];
        }

        return NULL;
    }

}
