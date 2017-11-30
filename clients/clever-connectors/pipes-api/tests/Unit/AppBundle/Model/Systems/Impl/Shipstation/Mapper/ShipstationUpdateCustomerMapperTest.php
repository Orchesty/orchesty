<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShipstationUpdateCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Mapper
 */
final class ShipstationUpdateCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shipstation-update-customer-mapper');

        $response = Json::decode($connector->process(
            (new ProcessDto())->setData(
                $this->getRequest('ShipstationSingleCustomerItem.json')
            ))->getData(), TRUE
        );

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'email@example.com',
            CleverFieldsEnum::FIRST_NAME => 'John',
            CleverFieldsEnum::LAST_NAME  => 'Doe',
            CleverFieldsEnum::FOREIGN_ID => '108140195',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}