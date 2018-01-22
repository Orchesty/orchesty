<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector\SalesforceGetLimitConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlException;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceGetLimitConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceGetLimitConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock(function (RequestDto $dto, array $options = []) {
            $this->assertEquals(
                new Uri('https://eu8.salesforce.com/services/data/v40.0/limits'),
                $dto->getUri()
            );

            return new ResponseDto(200, 'OK', $this->getRequest('SalesforceLimit.json'), []);
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
                new Uri('https://eu8.salesforce.com/services/data/v40.0/limits'),
                $dto->getUri()
            );

            throw new CurlException('', CurlException::REQUEST_FAILED, NULL,
                new Response(403, [], '"errorCode":"REQUEST_LIMIT_EXCEEDED"')
            );
        })->processAction((new ProcessDto())->setData(Json::encode([]))->setHeaders([]))->getHeaders();

        $this->assertEquals(['pf-result-code' => 1004], $headers);
    }

    /**
     * @param callable      $curlCallback
     * @param callable|NULL $loggerCallback
     * @param int           $count
     *
     * @return SalesforceGetLimitConnector
     */
    private function getConnectorMock(
        callable $curlCallback,
        ?callable $loggerCallback = NULL,
        int $count = 0
    ): SalesforceGetLimitConnector
    {
        $dto = (new RequestDto('POST', new Uri('https://eu8.salesforce.com')))
            ->setHeaders([
                'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQCPw1z3hchprOA9t08aqFqrY4RdcjNaEmRBHf170davnludWxhbo4WjBgWptw9OSk1yi1c4lfZm5RVo8h9sNsoGEysPd',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);

        /** @var SalesforceSystem|MockObject $system */
        $system = $this->createPartialMock(SalesforceSystem::class, ['getRequestDto', 'saveLimit']);
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

        $connector = new SalesforceGetLimitConnector($system, $documentManager, $curlManager);

        if ($loggerCallback) {
            /** @var LoggerInterface|MockObject $logger */
            $logger = $this->createMock(LoggerInterface::class);
            $logger->method('info')->willReturnCallback($loggerCallback);
            $connector->setLogger($logger);
        }

        return $connector;
    }

}