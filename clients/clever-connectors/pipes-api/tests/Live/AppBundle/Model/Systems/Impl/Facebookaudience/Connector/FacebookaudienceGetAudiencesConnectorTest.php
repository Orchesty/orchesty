<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Facebookaudience\FacebookaudienceSystem;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetAudiencesConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAudiencesConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws SystemException
     * @throws CleverConnectorsException
     * @throws ConnectorException
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.facebookaudience-get-audiences-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token'                     => 'EAAUmsI0AZCFEBAEYBMWBYZAhp8jO74JyhzHfc2ll3XJZCxDQZB1ZC3rB1oZBJt6R4hgJKgkIeZA06ScMepUZAd1Os7JZAMDafKiiWz76ZBZCYythm1Jllh9MkpGorLx9OUo9TsIDNZBqh0myy95UDBsQm4ZAZCygLcEXatHBcZD',
            FacebookaudienceSystem::AD_ACCOUNT => 'act_10203458258687988',
        ]));

        $data = Json::decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data'][0]);
        $this->assertArrayHasKey('name', $data['data'][0]);
    }

    /**
     *
     */
    public function testGetAudiences(): void
    {
        $this->markTestSkipped();

        $processDto = $this->prepareConnectorProcessDto([
            'access_token' => 'EAAUmsI0AZCFEBAEdbSZBiF4tAxQOReSMB6rWu88KBKoM6GLWYPPokhgLKHSeWmZA1dirRGey9kuCwZAui0LZAaPWzY50GtcM0SZBK60fkiIfoOZB1cWVgG7abN5fGavAZB0Jsv8l0mGgCjmVbgh6YbZAZApKVkb9N0lKoZD',
        ], [], [], TRUE);

        $data = Json::decode($processDto->getData(), TRUE);
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemInstallRepository->find($data['system_install']['_id']);

        $connector = $this->container->get('hbpf.connector.facebookaudience-get-audiences-connector');
        $audiences = $connector->getAudiences($systemInstall, [
            FacebookaudienceSystem::AD_ACCOUNT => 'act_10203458258687988',
        ]);

        $this->assertTrue(is_array($audiences));
        $this->assertTrue(count($audiences) > 0);
        $this->assertEquals('Create New', $audiences[FacebookaudienceSystem::CREATE_NEW]);
        unset($audiences[FacebookaudienceSystem::CREATE_NEW]);
        foreach ($audiences as $key => $audience) {
            $this->assertTrue(is_int($key));
            $this->assertTrue(is_string($audience));
        }
    }

}