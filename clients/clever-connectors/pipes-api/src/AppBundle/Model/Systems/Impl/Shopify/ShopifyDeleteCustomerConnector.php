<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

/**
 * Class ShopifyDeleteCustomerConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifyDeleteCustomerConnector extends ShopifyWebhookAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-delete-customer';
    }

}