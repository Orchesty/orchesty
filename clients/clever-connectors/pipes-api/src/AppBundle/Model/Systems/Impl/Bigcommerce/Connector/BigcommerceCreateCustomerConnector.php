<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

/**
 * Class BigcommerceCreateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceCreateCustomerConnector extends BigcommerceCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-create-customer-connector';
    }

}