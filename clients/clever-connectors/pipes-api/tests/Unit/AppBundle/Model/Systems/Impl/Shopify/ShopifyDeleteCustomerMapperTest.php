<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify;

use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyDeleteCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifyDeleteCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shopify-customer-delete-mapper');

        $response = Json::decode(
            $connector->processAction((new ProcessDto())->setData($this->getRequest()))->getData(),
            TRUE
        );

        $this->assertEquals([
            'email' => '131244785694',
        ], $response);
    }

}