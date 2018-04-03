<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Aim;

use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class AimSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Aim
 */
final class AimSystem extends PluginSystemAbstract
{

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'aim';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'AIM';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'AIM system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

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
    public function getAuthorizationType(): string
    {
        return self::BASIC;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function runSync(array $data): array
    {
        return [];
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function runDelete(array $data): bool
    {
        return TRUE;
    }

}
