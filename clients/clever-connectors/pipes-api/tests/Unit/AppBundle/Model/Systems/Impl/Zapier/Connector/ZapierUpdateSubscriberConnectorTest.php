<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector\ZapierUpdateSubscriberConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\ZapierSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZapierUpdateSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierUpdateSubscriberConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        /** @var RequestDto $requestDto */
        $requestDto  = NULL;
        $responseDto = new ResponseDto(200, 'OK', 'response body', []);

        /** @var MockObject|ZapierSystem $zapierSystem */
        $zapierSystem = $this->createMock(ZapierSystem::class);
        $zapierSystem->method('getRequestDto')->willReturn(new RequestDto('POST', new Uri()));

        /** @var MockObject|CurlManagerInterface $curl */
        $curl = $this->createMock(CurlManagerInterface::class);
        $curl->method('send')->willReturnCallback(function ($arg) use (&$requestDto, $responseDto) {
            $requestDto = $arg;

            return $responseDto;
        });

        $systemInstall = new SystemInstall();
        $systemInstall->setSettings([
            ZapierSystem::UPDATE_WEBHOOK_URL => 'http://webhook.url',
        ]);

        /** @var MockObject|SystemInstallRepository $systemInstallRepository */
        $systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($systemInstall);

        /** @var MockObject|DocumentManager $dm */
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('getRepository')->willReturn($systemInstallRepository);

        $connector = new ZapierUpdateSubscriberConnector($zapierSystem, $curl, $dm);

        $dto = new ProcessDto();
        $dto->setHeaders([]);
        $dto->setData('body data');

        $response = $connector->processAction($dto);

        $this->assertEquals('response body', $response->getData());
        $this->assertEquals('body data', $requestDto->getBody());
        $this->assertEquals('http://webhook.url', $requestDto->getUri(TRUE));
    }

}