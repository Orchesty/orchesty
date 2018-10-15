<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

/**
 * Class BigcommerceCreatedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceCreatedCustomerConnector extends BigcommerceCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-created-customer-connector';
    }

}