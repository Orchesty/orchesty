<?php

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;


use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCreateCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;


/**
 * Class QuickbooksCreateCustomerConnectorTest
 *
 * @coversDefaultClass \CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCreateCustomerConnector
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
class QuickbooksCreateCustomerConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var SystemInstall
     */
    protected $systemInstall;

    /**
     * @var QuickbooksSystem
     */
    protected $system;

    /**
     * @var LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * @var CurlSenderFactory
     */
    protected $sender;

    /**
     * @covers ::getId
     *
     * @return void
     */
    public function testGetId(): void
    {
        $this->initMocks();

        $connector = new QuickbooksCreateCustomerConnector(
            $this->system,
            $this->lastSyncManager,
            $this->sender
        );

        $id = $connector->getId();

        $this->assertEquals('quickbooks-create-customer-connector', $id);
    }

    /**
     * @covers ::processBatch()
     *
     * @return void
     */
    public function testProcessBatch(): void
    {
        $this->initMocks();

        $connector = new QuickbooksCreateCustomerConnector(
            $this->system,
            $this->lastSyncManager,
            $this->sender
        );

        $dtoData = [
            'system_install' => ['user' => '123'],
            'topology'       => ['name' => 'top'],
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $loop       = Factory::create();

        $promise = $connector->processBatch($processDto, $loop, function (): void {
        });

        $loop->run();
    }

    protected function initMocks()
    {

        $this->systemInstall = $this->createMock(SystemInstallRepository::class);
        $this->systemInstall->method('setSyncTime')->willReturn(NULL);

        $this->dm = $this->createMock(DocumentManager::class);
        $this->dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($this->systemInstall);

        $this->system = $this->createMock(QuickbooksSystem::class);
        $this->lastSyncManager = $this->createMock(LastSyncManager::class);
        $this->sender = $this->createMock(CurlSenderFactory::class);
    }

}