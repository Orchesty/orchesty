<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;

/**
 * Class NullSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl
 */
class NullSystem implements SystemInterface
{

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'NULL';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Only for testing purposes';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

}