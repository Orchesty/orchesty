<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Plugins\Connector\PluginUnsubscribeSubscriberConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class PluginUnsubscribeSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Plugins\Connector
 */
final class PluginUnsubscribeSubscriberConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new PluginUnsubscribeSubscriberConnector(
            $this->mockDm(),
            $this->mockCurl(),
            $this->container->get('cc.systems.loader')
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            CleverFieldsEnum::FOREIGN_ID => 'someId',
        ]));

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockDm(): DocumentManager
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            SystemInstall::SYSTEM_URL => 'https://neco.com',
        ])->setUser('guid')->setToken('tkn')->setSystem('null.user.group');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject
     */
    private function mockCurl(): CurlManagerInterface
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $dto = new RequestDto(CurlManager::METHOD_POST,
                        new Uri('https://neco.com/clever_connector/subscriber/unsubscribe?id=someId'));
                    $dto->setHeaders([]);

                    self::assertEquals($dto, $requestDto);

                    return new ResponseDto(200, '', '', []);
                }
            ));

        return $curl;
    }

}