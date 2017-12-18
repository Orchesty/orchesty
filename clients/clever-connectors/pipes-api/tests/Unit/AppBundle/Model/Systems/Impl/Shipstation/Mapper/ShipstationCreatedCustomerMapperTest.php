<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ShipstationCreatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Mapper
 */
final class ShipstationCreatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shipstation-created-customer-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'api_key'    => '3c836ae775da4c1e9c8d1263245c15c4',
            'api_secret' => '77dce1df889741598adebf4d96aebd1f',
        ], Json::decode($this->getRequest('ShipstationSingleCustomerItem.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '108140195',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessList(): void
    {
        $connector = $this->container->get('hbpf.custom_node.shipstation-created-customer-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'api_key'    => '3c836ae775da4c1e9c8d1263245c15c4',
            'api_secret' => '77dce1df889741598adebf4d96aebd1f',
            'list'       => '1802ef5f-c3d6-9520-fc06-08579b65dc92',
        ], Json::decode($this->getRequest('ShipstationSingleCustomerItem.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '108140195',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['1802ef5f-c3d6-9520-fc06-08579b65dc92'],
        ], $response);
    }

}