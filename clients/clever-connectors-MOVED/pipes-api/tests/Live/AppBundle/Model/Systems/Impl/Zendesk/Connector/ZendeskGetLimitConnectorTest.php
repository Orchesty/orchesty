<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskGetLimitConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskGetLimitConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.zendesk-get-limit-connector');
        $processDto = $connector->processAction(
            $this->prepareConnectorProcessDto(
                [
                    'api_token'  => 'EWnfF3o4k8vGlwjOe4u7FEp3FmESH65ydYZ6qOrk',
                    'user_email' => 'satabi2@gmail.com',
                    'domain'     => 'nohavica',
                ]
            )
        );

        $systemInstall = $this->dm->getRepository(SystemInstall::class)
            ->getSystemInstallFromHeaders($processDto->getHeaders());
        $settings      = $systemInstall->getSettings();
        $this->assertArrayHasKey(SystemInstall::SYSTEM_LIMITS, $settings);
        $this->assertArrayHasKey(SystemInstall::SYSTEM_LIMIT_UPDATE, $settings[SystemInstall::SYSTEM_LIMITS]);
        $this->assertEquals(400, $settings[SystemInstall::SYSTEM_LIMITS][SystemInstall::SYSTEM_LIMIT_VALUE]);
    }

}