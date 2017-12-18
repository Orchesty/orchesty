<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoCreatedContactMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zoho\Mapper
 */
final class ZohoCreatedContactMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zoho-created-contact-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
        ], Json::decode($this->getRequest('singleContact.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '85896000000078213',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessWithList(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zoho-created-contact-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
            'list'       => 'ffdfe93e-7a4e-0629-2a1a-27aee18a840a',
        ], Json::decode($this->getRequest('singleContact.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '85896000000078213',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['ffdfe93e-7a4e-0629-2a1a-27aee18a840a'],
        ], $response);
    }

}