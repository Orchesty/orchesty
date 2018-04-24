<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\Connector\PipedriveGetLimitsConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Pipedrive\PipedriveSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class PipedriveGetLimitsConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Pipedrive\Connector
 */
final class PipedriveGetLimitsConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testGetLimit(): void
    {
        $conn = new PipedriveGetLimitsConnector(
            new PipedriveSystem($this->dm),
            $this->mockDm(),
            $this->mockCurl()
        );

        $dto = new ProcessDto();
        $dto->setData('')->setHeaders([]);

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([PipedriveSystem::API_TOKEN => 'tkn']);

        /** @var SystemInstallRepository|PHPUnit_Framework_MockObject_MockObject $repo */
        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        /** @var DocumentManager|PHPUnit_Framework_MockObject_MockObject $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);
        $dm->expects($this->once())
            ->method('flush')->willReturn(TRUE);

        return $dm;
    }

    /**
     * @return CurlManagerInterface
     */
    private function mockCurl(): CurlManagerInterface
    {
        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $dto): ResponseDto {
                    $expt = new RequestDto('GET', new Uri('https://api.pipedrive.com/v1/permissionSets?api_token=tkn'));
                    $expt->setHeaders([
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ]);

                    self::assertEquals($expt, $dto);

                    $res = new ResponseDto(200, '', '', [
                        'X-RateLimit-Remaining' => '199',
                        'X-RateLimit-Reset'     => '10',
                        'Content-Type'          => 'application/json',
                        'X-RateLimit-Limit'     => '200',
                    ]);

                    return $res;
                }
            ));

        return $curl;
    }

}