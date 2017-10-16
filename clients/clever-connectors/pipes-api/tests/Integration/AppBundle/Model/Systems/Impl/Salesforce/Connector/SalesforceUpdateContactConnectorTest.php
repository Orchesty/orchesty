<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector;

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
 * Class SalesforceUpdateContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceUpdateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.salesforce-update-contact-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'access_token' => '00D1I000001WyE7!ARAAQIObuJ1gc6YcwxSsoolt9VvRJxyUeJZVP3ozOlKh9mQ8hxwgYNamy6_01wgynh4Lx.xqhHgZVGolJ4bm7lm2rkvsB9ZN',
            'instance_url' => 'https://na73.salesforce.com/',
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
            ->setNodeName('salesforce-sync-contanct-connector')
            ->setTimestamp(new DateTime('-3 days'));
        $this->persistAndFlush($lastSync);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

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

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::GUID)          => 'u_123',
            CMHeaders::createKey(CMHeaders::TOKEN)         => 't-456',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY)    => 's_-879',
            CMHeaders::createKey(CMHeaders::TOPOLOGY_NAME) => 'Topology',
            CMHeaders::createKey(CMHeaders::NODE_NAME)     => 'salesforce-sync-contanct-connector',
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