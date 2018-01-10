<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 1:51 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZapierCreatedSubscriberMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierCreatedSubscriberMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zapier-created-subscriber-mapper');
        $response  = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], Json::decode($this->getRequest('ZapierWebhookResponse.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'karel@barel.com',
            CleverFieldsEnum::FIRST_NAME => 'Karel',
            CleverFieldsEnum::LAST_NAME  => 'Barel',
            CleverFieldsEnum::FOREIGN_ID => '6',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
        ], $response);
    }

    /**
     *
     */
    public function testProcessList(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zapier-created-subscriber-mapper');
        $response  = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
            'list'     => '4b04d334-3db9-b290-d0aa-099642329856',
        ], Json::decode($this->getRequest('ZapierWebhookResponse.json'), TRUE), [], TRUE))->getData(), TRUE);

        $this->assertEquals([
            CleverFieldsEnum::EMAIL      => 'karel@barel.com',
            CleverFieldsEnum::FIRST_NAME => 'Karel',
            CleverFieldsEnum::LAST_NAME  => 'Barel',
            CleverFieldsEnum::FOREIGN_ID => '6',
            CleverFieldsEnum::REACTIVATE => TRUE,
            CleverFieldsEnum::SEND_OPTIN => FALSE,
            CleverFieldsEnum::LISTS      => ['4b04d334-3db9-b290-d0aa-099642329856'],
        ], $response);
    }

}