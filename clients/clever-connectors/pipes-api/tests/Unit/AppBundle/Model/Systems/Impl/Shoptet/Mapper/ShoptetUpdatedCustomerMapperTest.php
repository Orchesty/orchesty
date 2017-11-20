<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Mapper\ShoptetUpdatedCustomerMapper;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShoptetUpdatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Mapper
 */
class ShoptetUpdatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     * @covers ShoptetUpdatedCustomerMapper::process()
     */
    public function testProcess(): void
    {
        $mapper = $this->container->get('hbpf.custom_node.shoptet-updated-customer-mapper');

        $response = Json::decode($mapper->process(
            (new ProcessDto())->setData(
                $this->getRequest('ShoptetCustomerForMapper.json')
            ))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'abc@abc.com',
            CleverFieldsEnum::FIRST_NAME => 'asd',
            CleverFieldsEnum::LAST_NAME  => 'asddd',
            CleverFieldsEnum::FOREIGN_ID => '146278be-ca07-11e7-8216-002590dad85e',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

}