<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyUpdateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
class ShopifyUpdateCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shopify-update-customer-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('ShopifyUpdateCustomerMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            'email'                      => 'email@example.com',
            'first_name'                 => 'First',
            'last_name'                  => 'Last',
            CleverFieldsEnum::FOREIGN_ID => '129715699742',
        ], $response);
    }

}