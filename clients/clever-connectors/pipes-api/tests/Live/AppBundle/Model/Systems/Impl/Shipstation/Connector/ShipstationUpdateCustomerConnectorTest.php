<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Shipstation\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ShipstationUpdateCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Shipstation\Connector
 */
final class ShipstationUpdateCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.shipstation-update-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'api_key'    => '816716bc3a654313b6cc17731df72032',
            'api_secret' => 'b71662f2560749779939627dc5df4d37',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $processDto = (new ProcessDto())
            ->setData(Json::encode([
                'system_install' => [
                    '_id'               => $system->getId(),
                    'user'              => $system->getUser(),
                    'token'             => $system->getToken(),
                    'system'            => $system->getSystem(),
                    'encryptedSettings' => CryptManager::encrypt($settings),
                ],
                'topology'       => ['name' => 'top-name-ever'],
            ]))->setHeaders([
                'pf-guid'          => $system->getUser(),
                'pf-token'         => $system->getToken(),
                'pf-system-key'    => $system->getSystem(),
                'pf-topology-name' => 'top-name-ever',
                'pf-node-name'     => 'node-name',
            ]);

        $loop    = Factory::create();
        $process = $connector->processBatch($processDto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        });

        $process->then(
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