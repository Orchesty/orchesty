<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyDeleteCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
class ShopifyDeleteCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shopify-delete-customer-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('ShopifyDeleteCustomerMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'                      => '131244785694',
            CleverFieldsEnum::FOREIGN_ID => '131244785694',
        ], $response);
    }

}