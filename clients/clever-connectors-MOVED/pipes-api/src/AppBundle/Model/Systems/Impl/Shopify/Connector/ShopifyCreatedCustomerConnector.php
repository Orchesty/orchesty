<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

/**
 * Class ShopifyCreatedCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifyCreatedCustomerConnector extends ShopifyCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-created-customer-connector';
    }

}