<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use React\EventLoop\Factory;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class BigcommerceSyncCustomerConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceSyncCustomerConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $this->markTestSkipped();
        $connector = $this->container->get('hbpf.connector.bigcommerce-sync-customer-connector');

        $topology = (new Topology())->setName('Topology');
        $this->persistAndFlush($topology);

        $settings = [
            'store_id'     => 'ukcfcghi',
            'client_id'    => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'access_token' => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
        ];

        $system = (new SystemInstall())
            ->setUser('u_123')
            ->setToken('t-456')
            ->setSystem('s_-879')
            ->setSettings($settings);
        $this->persistAndFlush($system);

        $node = (new Node())
            ->setName('Node')
            ->setTopology($topology->getId());
        $this->persistAndFlush($node);

        $loop       = Factory::create();
        $processDto = (new ProcessDto())->setHeaders([
            'pf-guid'       => $system->getUser(),
            'pf-token'      => $system->getToken(),
            'pf-system-key' => $system->getSystem(),
            'pf-process-id' => '123456',
        ]);

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

        $this->dm->clear();
        /** @var SystemInstall $system */
        $system = $this->dm->getRepository(SystemInstall::class)->find($system->getId());
        $this->assertInstanceOf(SystemInstall::class, $system);
        $this->assertTrue($system->isSynchronized());
    }

}