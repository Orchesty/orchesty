<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceCreateSubscribersConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceCreateSubscribersConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws SystemException
     * @throws CleverConnectorsException
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();

        $hashedEmails = [
            hash('sha256', 'aaa@aaa.com'),
            hash('sha256', 'bbb@bbb.com'),
        ];

        $connector  = $this->container->get('hbpf.connector.facebookaudience-create-subscribers-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token'                          => 'EAAUmsI0AZCFEBAEYBMWBYZAhp8jO74JyhzHfc2ll3XJZCxDQZB1ZC3rB1oZBJt6R4hgJKgkIeZA06ScMepUZAd1Os7JZAMDafKiiWz76ZBZCYythm1Jllh9MkpGorLx9OUo9TsIDNZBqh0myy95UDBsQm4ZAZCygLcEXatHBcZD',
            FacebookaudienceSystem::CUSTOM_AUDIENCE => '23842685412090690',
        ], [
            'payload' => [
                'schema' => 'EMAIL_SHA256',
                'data'   => $hashedEmails,
            ],
        ]));

        $data = Json::decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($data));
        $this->assertEquals('23842685412090690', $data['audience_id']);
        $this->assertEquals(2, $data['num_received']);
        $this->assertEquals(0, $data['num_invalid_entries']);
    }

}