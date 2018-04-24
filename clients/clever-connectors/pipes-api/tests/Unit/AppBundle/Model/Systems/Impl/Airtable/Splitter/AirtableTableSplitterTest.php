<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Splitter;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Splitter\AirtableTableSplitter;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;

/**
 * Class AirtableTableSplitterTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Splitter
 */
class AirtableTableSplitterTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $conn = new AirtableTableSplitter(
            $this->mockCounter(),
            $this->mockDm()
        );

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData('');

        $data = $conn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

    /**
     * @return ProgressCounterService
     */
    private function mockCounter(): ProgressCounterService
    {
        /** @var ProgressCounterService|PHPUnit_Framework_MockObject_MockObject $processCounter */
        $counter = $this->createMock(ProgressCounterService::class);
        $counter->method('setTotal')->willReturn(TRUE);

        return $counter;
    }

    /**
     * @return DocumentManager
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([

        ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

}