<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Magento2\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class Magento2UpdateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Magento2\Mapper
 */
final class Magento2UpdateCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.magento2-update-customer-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('Magento2SingleCustomerItem.json')
            ))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'email@example.com',
            CleverFieldsEnum::FIRST_NAME => 'John',
            CleverFieldsEnum::LAST_NAME  => 'Doe',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
        ], $response);
    }

}