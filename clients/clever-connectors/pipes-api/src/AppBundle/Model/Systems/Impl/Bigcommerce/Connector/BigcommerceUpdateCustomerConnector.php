<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

/**
 * Class BigcommerceUpdateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceUpdateCustomerConnector extends BigcommerceCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-update-customer-connector';
    }

}