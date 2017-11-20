<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Shoptet\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class ShoptetSyncCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Shoptet\Connector
 */
final class ShoptetSyncCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.shoptet-sync-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'url' => 'https://179974.myshoptet.com/export/customers.xml?ip=188.122.212.69&hash=31c6bc83857bb10328887befd0f41d4f81e6cf6a88ee644aa77e250c4d9efbd6',
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
            'pf-guid'       => $system->getUser(),
            'pf-token'      => $system->getToken(),
            'pf-system-key' => $system->getSystem(),
        ]);

        $loop    = Factory::create();
        $process = $connector->processBatch($processDto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(strpos($message->getData(), '<?xml') === 0);
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