<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

/**
 * Class BigcommerceDeleteCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceDeleteCustomerConnector extends BigcommerceCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-delete-customer-connector';
    }

}