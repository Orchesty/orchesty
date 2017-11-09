<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class HubspotSyncContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
final class HubspotSyncContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.hubspot-sync-contact-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            "access_token"  => "COPd6sr0KxICAQEY75vzASD-46cCKL-1AzIZADUnP_ZsvWK5R1k2VxSQarO2253kiPZ3PQ",
            "expires_in"    => 21600,
            "refresh_token" => "221f086d-1760-4cb2-8260-c6833137bdd7",
            "app_id"        => 55999,
        ];

        $system = new SystemInstall();
        $system
            ->setUser('hs_u_123')
            ->setToken('hs_t-456')
            ->setSystem('hs_s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $dtoData = [
            'data' => [
                'system_install' => [
                    '_id'               => $system->getId(),
                    'user'              => $system->getUser(),
                    'token'             => $system->getToken(),
                    'system'            => $system->getSystem(),
                    'encryptedSettings' => CryptManager::encrypt($settings),
                ],
                'topology'       => ['name' => 'top-name-ever'],
            ],
        ];

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $headers[CMHeaders::createKey(CMHeaders::GUID)]       = $system->getUser();
        $headers[CMHeaders::createKey(CMHeaders::TOKEN)]      = $system->getToken();
        $headers[CMHeaders::createKey(CMHeaders::SYSTEM_KEY)] = $system->getSystem();

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders($headers);

        $loop = Factory::create();

        $process = $connector->processBatch($processDto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        });

        $process->then(
            function (): void {
                $this->assertTrue(TRUE);
            }
        )->done();

        $loop->run();

        $this->dm->clear();
        /** @var SystemInstall $sys */
        $sys = $this->dm->getRepository(SystemInstall::class)->find($system->getId());
        $this->assertInstanceOf(SystemInstall::class, $sys);
        $this->assertTrue($sys->isSynchronized());
    }

}