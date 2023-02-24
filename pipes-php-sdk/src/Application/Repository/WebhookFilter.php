<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Repository;

use Hanaboso\PipesPhpSdk\Storage\Mongodb\Filter;

/**
 * Class WebhookFilter
 *
 * @package Hanaboso\PipesPhpSdk\Application\Repository
 */
final class WebhookFilter extends Filter
{

    /**
     * WebhookFilter constructor.
     *
     * @param mixed[]|null $applications
     * @param mixed[]|null $userIds
     * @param mixed[]|null $ids
     * @param bool|null    $deleted
     */
    public function __construct(
        public ?array $applications = NULL,
        public ?array $userIds = NULL,
        ?array $ids = NULL,
        ?bool $deleted = NULL,
    )
    {
        parent::__construct($ids, $deleted);
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        $retArray = parent::toArray();

        if ($this->applications) $retArray['applications'] = $this->applications;
        if ($this->userIds) $retArray['user_uds']          = $this->userIds;

        return $retArray;
    }

}
