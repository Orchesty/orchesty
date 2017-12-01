<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Amq\CMActivatorProducer;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventActivator;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class CMEventActivatorTest
 *
 * @package Tests\Unit\AppBundle\Model\CMEvents
 */
class CMEventActivatorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        /** @var CMActivatorProducer|MockObject $producer */
        $producer = $this->createMock(CMActivatorProducer::class);

        $node = new CMEventActivator(
            $this->mockSystemManager(),
            $this->mockDm(),
            $this->mockCurl(),
            $producer
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            SystemInstall::EVENT_HARD_BOUNCE,
            SystemInstall::EVENT_CREATE,
        ]));

        $loop = Factory::create();
        $data = $node->processBatch($dto, $loop, function (): void {
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
     * @return SystemManager|MockObject
     */
    private function mockSystemManager(): SystemManager
    {
        $manager = $this->createMock(SystemManager::class);
        $manager->expects($this->once())
            ->method('getSystem')->willReturn($this->container->get('systems.null.user.group'));

        return $manager;
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setSystem('sys');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlSenderFactory|MockObject
     */
    private function mockCurl(): CurlSenderFactory
    {
        $test = $this;

        $curl = $this->createMock(CurlSender::class);
        $curl->expects($this->at(0))
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) use ($test) {
                $expt = new RequestDto('POST', new Uri(''));
                $expt->setHeaders([])
                    ->setBody('');

                $test->assertEquals($expt, $requestDto);

                return resolve(new Response(200, [], 'msg'));
            }));
        $curl->expects($this->at(1))
            ->method('send')->will($this->returnCallback(function (RequestDto $requestDto) use ($test) {
                $expt = new RequestDto('POST', new Uri(''));
                $expt->setHeaders([])
                    ->setBody('');

                $test->assertEquals($expt, $requestDto);

                return resolve(new Response(200, [], 'msg'));
            }));

        $fac = $this->createMock(CurlSenderFactory::class);
        $fac->method('create')->willReturn($curl);

        return $fac;
    }

}