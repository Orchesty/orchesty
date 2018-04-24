<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\PluginHeadersEnum;
use CleverConnectors\AppBundle\Model\Plugins\Connector\PluginSwitchTokenConnector;
use CleverConnectors\AppBundle\Model\Systems\SystemLoader;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class PluginSwitchTokenConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Plugins\Connector
 */
final class PluginSwitchTokenConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new PluginSwitchTokenConnector(
            $this->mockDm(),
            $this->mockCurl(),
            $this->mockLoader()
        );

        $dto = new ProcessDto();
        $dto
            ->setHeaders(['pf-token' => 'header-token'])
            ->setData(json_encode([
                'token' => 'body-token',
            ]));

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm()
    {
        $sys = new SystemInstall();
        $sys
            ->setSettings([
                SystemInstall::SYSTEM_URL => 'https://neco.com',
                SystemInstall::TOKEN      => 'header-token',
            ])
            ->setUser('guid')
            ->setToken('header-token')
            ->setSystem('null.user.group');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo
            ->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return SystemLoader|MockObject
     */
    private function mockLoader()
    {
        $system = $this->createPartialMock(NullSystem::class, ['getRequestDto']);
        $system
            ->expects($this->once())
            ->method('getRequestDto')
            ->willReturnCallback(
                function (SystemInstall $systemInstall) {
                    $dto = new RequestDto(CurlManager::METHOD_POST, new Uri());
                    $dto->setHeaders([
                        PluginHeadersEnum::TOKEN => $systemInstall->getToken(),
                    ]);

                    return $dto;
                }
            );

        $loader = $this->createMock(SystemLoader::class);
        $loader
            ->expects($this->once())
            ->method('getSystem')
            ->willReturn($system);

        return $loader;
    }

    /**
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl()
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $dto = new RequestDto(
                        CurlManager::METHOD_POST,
                        new Uri('https://neco.com/clever_connector/switch_token')
                    );
                    $dto
                        ->setHeaders(['cm-token' => 'body-token'])
                        ->setBody('{"token":"header-token"}');

                    self::assertEquals($dto, $requestDto);

                    return new ResponseDto(200, '', '', []);
                }
            ));

        return $curl;
    }

}