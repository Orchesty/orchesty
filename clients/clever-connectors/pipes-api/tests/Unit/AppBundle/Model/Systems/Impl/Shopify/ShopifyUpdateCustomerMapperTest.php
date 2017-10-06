<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyUpdateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifyUpdateCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.connectors.shopify-customer-update-mapper');

        $response = Json::decode(
            $connector->processAction((new ProcessDto())->setData($this->getRequest()))->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'      => 'email@example.com',
            'first_name' => 'First',
            'last_name'  => 'Last',
            'company'    => 'Company',
            'contact'    => 'City',
        ], $response);
    }

}