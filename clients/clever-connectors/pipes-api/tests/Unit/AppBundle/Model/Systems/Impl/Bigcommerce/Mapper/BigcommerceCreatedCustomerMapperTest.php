<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceCreatedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
final class BigcommerceCreatedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.bigcommerce-created-customer-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'store_id'     => 'noos7j71hh',
            'client_id'    => '7nwemuc6smvr35a688gga31jdgsmw7d',
            'access_token' => 'dk44ci1tclbn8j6ooud5akbrjsa7l1j',
        ], Json::decode($this->getRequest('BigcommerceSingleCustomerItem.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessList(): void
    {
        $connector = $this->container->get('hbpf.custom_node.bigcommerce-created-customer-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'store_id'     => 'noos7j71hh',
            'client_id'    => '7nwemuc6smvr35a688gga31jdgsmw7d',
            'access_token' => 'dk44ci1tclbn8j6ooud5akbrjsa7l1j',
            'list'         => 'd2a151ff-7548-c8e4-b05d-694eae055b26',
        ], Json::decode($this->getRequest('BigcommerceSingleCustomerItem.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'User01@User01.com',
            CleverFieldsEnum::FIRST_NAME => 'User01',
            CleverFieldsEnum::LAST_NAME  => 'User01',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['d2a151ff-7548-c8e4-b05d-694eae055b26'],
        ], $response);
    }

}