<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceCreateAudienceConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateAudienceConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws SystemException
     * @throws CleverConnectorsException
     * @throws ConnectorException
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.facebookaudience-create-audience-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token'                          => 'EAAUmsI0AZCFEBAEYBMWBYZAhp8jO74JyhzHfc2ll3XJZCxDQZB1ZC3rB1oZBJt6R4hgJKgkIeZA06ScMepUZAd1Os7JZAMDafKiiWz76ZBZCYythm1Jllh9MkpGorLx9OUo9TsIDNZBqh0myy95UDBsQm4ZAZCygLcEXatHBcZD',
            FacebookaudienceSystem::AD_ACCOUNT      => 'act_10203458258687988',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => FacebookaudienceSystem::CREATE_NEW,
            FacebookaudienceSystem::NEW_LIST        => 'Test 01',
        ], [
            'data' => [
                [
                    'id'   => '123',
                    'name' => 'abc',
                ],
            ],
        ]));

        $data = Json::decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('id', $data);
    }

}