<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ZendeskSyncUserConnectorTest
 *
 * @coversDefaultClass CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskSyncUserConnector
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskSyncUserConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers ::processBatch()
     * @covers ::getPage()
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.zendesk-sync-user-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'api_token'  => 'DQkrXS6exPswWj7pA3Qqo3ZxVDMmAliuiLdNiIDs',
            'user_email' => 'zen@mailinator.com',
            'domain'     => 'hbpf',
        ];

        $system = new SystemInstall();
        $system
            ->setUser('usr22')
            ->setToken('ttkn')
            ->setSystem('654')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $dtoData = [];

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