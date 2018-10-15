<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AirtableUpdatedContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableUpdatedContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.airtable-updated-contact-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'url'     => 'https://api.airtable.com/v0/app91I09gFeMUscCG/Table%201',
            'api_key' => 'keyuejqSEf94ZjN11',
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

        $processDto = (new ProcessDto())->setHeaders([
            'pf-guid'          => $system->getUser(),
            'pf-token'         => $system->getToken(),
            'pf-system-key'    => $system->getSystem(),
            'pf-topology-name' => $topology->getName(),
            'pf-node-name'     => $node->getName(),
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

        $this->dm->clear();
        /** @var SystemInstall $sys */
        $sys = $this->dm->getRepository(SystemInstall::class)->find($system->getId());
        $this->assertInstanceOf(SystemInstall::class, $sys);
    }

}