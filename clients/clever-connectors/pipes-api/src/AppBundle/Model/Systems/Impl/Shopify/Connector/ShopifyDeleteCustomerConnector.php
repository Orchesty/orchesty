<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

/**
 * Class ShopifyDeleteCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifyDeleteCustomerConnector extends ShopifyCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-delete-customer-connector';
    }

}