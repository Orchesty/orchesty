<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Wisepops\Connector;

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
 * Class WisepopsSyncEmailConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Wisepops\Connector
 */
final class WisepopsSyncEmailConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers WisepopsSyncEmailConnector::processBatch()
     * @covers WisepopsSyncEmailConnector::getPage()
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.wisepops-sync-email-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'api_key' => '$2y$10$W4bsH4haTHOk04Oip9seTuvDcrcbdPxwtZDZwaWZQkLyuCfXNnwu6',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('usr22')
            ->setToken('ttkn')
            ->setSystem('654')
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

        $processDto = (new ProcessDto())->setData(Json::encode($dtoData))->setHeaders([
            CMHeaders::createKey(CMHeaders::TOKEN)      => 'ttkn',
            CMHeaders::createKey(CMHeaders::GUID)       => 'usr22',
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => '654',
        ]);

        $loop = Factory::create();

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

        $this->dm->clear();
        /** @var SystemInstall $sys */
        $sys = $this->dm->getRepository(SystemInstall::class)->find($system->getId());
        $this->assertInstanceOf(SystemInstall::class, $sys);
        $this->assertTrue($sys->isSynchronized());
    }

}