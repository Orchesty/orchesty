<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

/**
 * Class ShopifyCreateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifyCreateCustomerConnector extends ShopifyCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-create-customer-connector';
    }

}