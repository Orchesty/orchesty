<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskSyncUserConnector;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ZohoSyncContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoSyncContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers ZendeskSyncUserConnector::processBatch()
     * @covers ZendeskSyncUserConnector::getPage()
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.zoho-sync-contact-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'auth_token' => '0a14af682cbee191575e7f43014c32ad',
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