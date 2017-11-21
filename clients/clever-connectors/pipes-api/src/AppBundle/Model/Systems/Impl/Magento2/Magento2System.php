<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Magento2;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class Magento2System
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Magento2
 */
class Magento2System extends PluginSystemAbstract
{

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'magento2';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Magento2';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Magento2';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}
