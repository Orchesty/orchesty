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
 * Class ZapierUpdatedSubscriberMapperTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Mapper
 */
class ZapierUpdatedSubscriberMapperTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcess(): void
    {
        $connector = $this->container->get('hbpf.custom_node.zapier-updated-subscriber-mapper');
        $response  = Json::decode($connector->process($this->prepareConnectorProcessDto([
            'user'  => 'User',
            'token' => 'Token',
            'list'  => 'd70671e4-7b87-805e-2fc5-a673d2316330',
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

}