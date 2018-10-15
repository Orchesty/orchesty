<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\Connector\ZendeskGetLimitConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zendesk\ZendeskSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\CommonsBundle\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZendeskGetLimitConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zendesk\Connector
 */
final class ZendeskGetLimitConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock(function (RequestDto $dto, array $options = []) {
            $this->assertEquals(
                new Uri('https://nohavica.zendesk.com/api/v2/account/settings.json'),
                $dto->getUri()
            );

            return new ResponseDto(200, 'OK', '', ['X-Rate-Limit' => [0 => 400]]);
        }, NULL, 1)->processAction(
            (new ProcessDto())->setData(Json::encode([]))->setHeaders([])
        )->getData(), TRUE);

        $this->assertEquals([], $result);
    }

    /**
     *
     */
    public function testProcessActionWithLimiter(): void
    {
        $headers = $this->getConnectorMock(function (RequestDto $dto, array $options = []): void {
            $this->assertEquals(
                new Uri('https://nohavica.zendesk.com/api/v2/account/settings.json'),
                $dto->getUri()
            );

            throw new CurlException('', CurlException::REQUEST_FAILED, NULL,
                new Response(429, [], '')
            );
        })->processAction((new ProcessDto())->setData(Json::encode([]))->setHeaders([]))->getHeaders();

        $this->assertEquals(['pf-result-code' => 1004], $headers);
    }

    /**
     * @param callable      $curlCallback
     * @param callable|NULL $loggerCallback
     * @param int           $count
     *
     * @return ZendeskGetLimitConnector
     */
    private function getConnectorMock(
        callable $curlCallback,
        ?callable $loggerCallback = NULL,
        int $count = 0
    ): ZendeskGetLimitConnector
    {
        $dto = (new RequestDto('POST', new Uri('https://nohavica.zendesk.com')))
            ->setHeaders([
                'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQCPw1z3hchprOA9t08aqFqrY4RdcjNaEmRBHf170davnludWxhbo4WjBgWptw9OSk1yi1c4lfZm5RVo8h9sNsoGEysPd',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);

        /** @var ZendeskSystem|MockObject $system */
        $system = $this->createPartialMock(ZendeskSystem::class, ['getRequestDto', 'saveLimit']);
        $system->method('getRequestDto')->willReturn($dto);
        $system
            ->expects($count ? $this->once() : $this->never())
            ->method('saveLimit')
            ->willReturn(new SystemInstall());

        /** @var SystemInstall|MockObject $systemInstall */
        $systemInstall = $this->createPartialMock(SystemInstallRepository::class, ['getSystemInstallFromHeaders']);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn(
            (new SystemInstall())
                ->setUser('User')
                ->setToken('Token')
        );

        /** @var DocumentManager|MockObject $documentManager */
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->method('getRepository')->willReturn($systemInstall);
        $documentManager
            ->expects($count ? $this->once() : $this->never())
            ->method('flush')
            ->willReturn(TRUE);

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback($curlCallback);

        $connector = new ZendeskGetLimitConnector($system, $documentManager, $curlManager);

        if ($loggerCallback) {
            /** @var LoggerInterface|MockObject $logger */
            $logger = $this->createMock(LoggerInterface::class);
            $logger->method('info')->willReturnCallback($loggerCallback);
            $connector->setLogger($logger);
        }

        return $connector;
    }

}