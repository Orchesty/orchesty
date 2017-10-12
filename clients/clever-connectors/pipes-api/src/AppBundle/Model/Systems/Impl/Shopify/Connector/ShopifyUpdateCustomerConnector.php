<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

/**
 * Class ShopifyUpdateCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
class ShopifyUpdateCustomerConnector extends ShopifyCustomerConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-update-customer';
    }

}