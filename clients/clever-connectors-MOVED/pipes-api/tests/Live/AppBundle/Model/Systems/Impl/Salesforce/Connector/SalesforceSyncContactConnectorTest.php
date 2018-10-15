<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceSyncContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceSyncContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.salesforce-sync-contact-connector');
        $processDto = $this->prepareConnectorProcessDto([
            'access_token' => '00D1I000001WyE7!ARAAQPT.YKcCvNCD6rFS.DD_N7cxWbz8hHppPhTSyvwCEWv5JqbemqJmXkWKoK8dGzYiZdpDEJjvI3V2Dv9EuIr6v0xd31Vs',
            'instance_url' => 'https://na73.salesforce.com/',
        ]);

        $loop = Factory::create();
        $connector->processBatch($processDto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        })->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
        $this->dm->clear();

        $systemInstall = $this->systemInstallRepository->getSystemInstallFromHeaders($processDto->getHeaders());
        $this->assertInstanceOf(SystemInstall::class, $systemInstall);
        $this->assertTrue($systemInstall->isSynchronized());
    }

}