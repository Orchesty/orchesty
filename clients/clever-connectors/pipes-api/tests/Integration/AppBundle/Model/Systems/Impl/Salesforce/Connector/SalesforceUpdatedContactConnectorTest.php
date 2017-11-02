<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceUpdatedContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceUpdatedContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.salesforce-updated-contact-connector');
        $processDto = $this->prepareConnectorProcessDto([
            'access_token' => '00D1I000001WyE7!ARAAQPT.YKcCvNCD6rFS.DD_N7cxWbz8hHppPhTSyvwCEWv5JqbemqJmXkWKoK8dGzYiZdpDEJjvI3V2Dv9EuIr6v0xd31Vs',
            'instance_url' => 'https://na73.salesforce.com/',
        ], [], [
            'pf-topology-name' => 'topology-name',
            'pf-node-name'     => 'node-name',
        ], TRUE);

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
    }

}