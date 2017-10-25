<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ZohoUpdateContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoUpdateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.zoho-update-contact-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'auth_token'  => '0a14af682cbee191575e7f43014c32ad',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $lastSync = new LastSync();
        $lastSync->setUser('u_123')
            ->setTopologyName('Topology')
            ->setNodeName('zendesk-update-user-connector')
            ->setTimestamp(new DateTime('-3 days'));
        $this->persistAndFlush($lastSync);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $dtoData = [
            'system_install' => [
                '_id'               => $system->getId(),
                'user'              => $system->getUser(),
                'token'             => $system->getToken(),
                'system'            => $system->getSystem(),
                'encryptedSettings' => CryptManager::encrypt($settings),
            ],
        ];

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::GUID)          => 'u_123',
            CMHeaders::createKey(CMHeaders::TOKEN)         => 't-456',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY)    => 's_-879',
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => 'Topology',
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'zendesk-update-user-connector',
        ]);
        $loop       = Factory::create();

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