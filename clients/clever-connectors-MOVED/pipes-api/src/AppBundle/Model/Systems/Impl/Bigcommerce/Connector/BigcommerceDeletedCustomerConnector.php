<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

/**
 * Class BigcommerceDeletedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
class BigcommerceDeletedCustomerConnector extends BigcommerceCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'bigcommerce-deleted-customer-connector';
    }

}