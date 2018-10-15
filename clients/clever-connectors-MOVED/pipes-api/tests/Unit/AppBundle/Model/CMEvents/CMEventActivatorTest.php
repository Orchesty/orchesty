<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CMEvents;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventActivator;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use ReflectionException;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;
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
     * @throws ReflectionException
     * @throws SystemException
     */
    public function testProcessBatch(): void
    {
        /** @var SystemLoader|MockObject $producer */
        $loader = $this->createMock(SystemLoader::class);
        $loader->method('getSystem')->willReturn(new NullSystem());

        $node = new CMEventActivator(
            $this->mockSystemManager(),
            $this->mockDm(),
            $this->mockCurl(),
            $loader
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
     * @return SystemManager
     * @throws ReflectionException
     */
    private function mockSystemManager(): SystemManager
    {
        /** @var SystemManager|MockObject $manager */
        $manager = $this->createMock(SystemManager::class);
        $manager->expects($this->once())
            ->method('getSystem')->willReturn($this->ownContainer->get('systems.null.user.group'));

        return $manager;
    }

    /**
     * @return DocumentManager
     * @throws ReflectionException
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setUser('usr')
            ->setSystem('sys');

        /** @var SystemInstallRepository|MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlSenderFactory
     * @throws ReflectionException
     */
    private function mockCurl(): CurlSenderFactory
    {
        $test = $this;

        /** @var CurlSender|MockObject $curl */
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

        /** @var CurlSenderFactory|MockObject $fac */
        $fac = $this->createMock(CurlSenderFactory::class);
        $fac->method('create')->willReturn($curl);

        return $fac;
    }

}