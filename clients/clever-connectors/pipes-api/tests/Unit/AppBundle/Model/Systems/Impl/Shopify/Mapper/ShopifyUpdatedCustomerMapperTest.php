<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShopifyUpdatedCustomerMapperTest
 *
 * @coversDefaultClass CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper\ShopifyUpdatedCustomerMapper
 * @package            Tests\Unit\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
final class ShopifyUpdatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers ::process()
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shopify-updated-customer-mapper');

        $response = Json::decode(
            $connector->process((new ProcessDto())->setData($this->getRequest('ShopifyUpdatedCustomerMapper.json')))
                ->getData(),
            TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'email@example.com',
            CleverFieldsEnum::FIRST_NAME => 'First',
            CleverFieldsEnum::LAST_NAME  => 'Last',
            CleverFieldsEnum::FOREIGN_ID => '129715699742',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}