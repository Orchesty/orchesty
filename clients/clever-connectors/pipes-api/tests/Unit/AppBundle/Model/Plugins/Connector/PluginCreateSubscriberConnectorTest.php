<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Plugins\Connector\PluginCreateSubscriberConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class PluginCreateSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Plugins\Connector
 */
final class PluginCreateSubscriberConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        $conn = new PluginCreateSubscriberConnector(
            $this->mockDm(),
            $this->mockCurl(),
            $this->ownContainer->get('cc.systems.loader')
        );

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData(json_encode([
            CleverFieldsEnum::EMAIL      => 'eml@eml.com',
            CleverFieldsEnum::FIRST_NAME => 'ichi',
            CleverFieldsEnum::LAST_NAME  => 'ni',
        ]));

        $conn->processAction($dto);
    }

    /**
     * @return DocumentManager|MockObject
     */
    private function mockDm()
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
     * @return CurlManagerInterface|MockObject
     */
    private function mockCurl()
    {
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->expects($this->once())
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $dto = new RequestDto(CurlManager::METHOD_POST,
                        new Uri('https://neco.com/clever_connector/subscriber/create'));
                    $dto->setHeaders([])->setBody(json_encode([
                        'email'      => 'eml@eml.com',
                        'first_name' => 'ichi',
                        'last_name'  => 'ni',
                    ]));

                    self::assertEquals($dto, $requestDto);

                    return new ResponseDto(200, '', '', []);
                }
            ));

        return $curl;
    }

}