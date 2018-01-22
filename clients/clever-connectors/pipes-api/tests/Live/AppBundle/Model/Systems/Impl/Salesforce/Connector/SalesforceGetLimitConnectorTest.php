<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceGetLimitConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceGetLimitConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.salesforce-get-limit-connector');
        $processDto = $connector->processAction(
            $this->prepareConnectorProcessDto(
                [
                    'access_token' => '00D0N000000h2Wg!AQEAQG8c1_u95oGDIy9yQ4i9e7FRZf9vv09kzeNuZbZcBs1ka9tS40AWtOhqwuXilxGU4FifjBdJHnnufOxnX789FyeXjQQI',
                    'instance_url' => 'https://eu8.salesforce.com/',
                ]
            )
        );

        $systemInstall = $this->dm->getRepository(SystemInstall::class)
            ->getSystemInstallFromHeaders($processDto->getHeaders());
        $settings      = $systemInstall->getSettings();
        $this->assertArrayHasKey(SystemInstall::SYSTEM_LIMITS, $settings);
        $this->assertArrayHasKey(SystemInstall::SYSTEM_LIMIT_UPDATE, $settings);
        $this->assertEquals(15000, $settings[SystemInstall::SYSTEM_LIMITS]);
    }

}