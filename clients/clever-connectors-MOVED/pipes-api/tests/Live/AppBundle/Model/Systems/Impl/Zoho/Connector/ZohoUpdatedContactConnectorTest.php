<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zoho\Connector;

use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoUpdatedContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoUpdatedContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.zoho-updated-contact-connector');
        $processDto = $this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
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