<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceDeletedCustomerMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
final class BigcommerceDeletedCustomerMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.bigcommerce-deleted-customer-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'store_id'     => 'noos7j71hh',
            'client_id'    => '7nwemuc6smvr35a688gga31jdgsmw7d',
            'access_token' => 'dk44ci1tclbn8j6ooud5akbrjsa7l1j',
        ], ['id' => 1], [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => '1',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => FALSE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessList(): void
    {
        $connector = $this->container->get('hbpf.custom_node.bigcommerce-deleted-customer-mapper');

        $response = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'store_id'     => 'noos7j71hh',
            'client_id'    => '7nwemuc6smvr35a688gga31jdgsmw7d',
            'access_token' => 'dk44ci1tclbn8j6ooud5akbrjsa7l1j',
            'list'         => 'd2a151ff-7548-c8e4-b05d-694eae055b26',
        ], ['id' => 1], [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => '1',
            CleverFieldsEnum::FOREIGN_ID => '1',
            CleverFieldsEnum::REACTIVATE => FALSE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

}