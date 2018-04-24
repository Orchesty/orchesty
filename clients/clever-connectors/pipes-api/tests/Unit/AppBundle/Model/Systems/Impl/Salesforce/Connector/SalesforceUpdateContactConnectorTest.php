<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector\SalesforceUpdateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
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
 * Class SalesforceUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $result = Json::decode($this->getConnectorMock(function (RequestDto $dto, array $options = []) {
            $this->assertEquals(
                new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact/id/0031I0000044B1kQAE'),
                $dto->getUri()
            );

            return new ResponseDto(200, 'OK', '{}', []);
        })->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => '0031I0000044B1kQAE',
            ]))->setHeaders([
                CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
            ])
        )->getData(), TRUE);

        $this->assertEmpty($result);
    }

    /**
     *
     */
    public function testProcessActionWithLimiter(): void
    {
        $headers = $this->getConnectorMock(function (RequestDto $dto, array $options = []): void {
            $this->assertEquals(
                new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact/id/0031I0000044B1kQAE'),
                $dto->getUri()
            );

            throw new CurlException('', CurlException::REQUEST_FAILED, NULL,
                new Response(403, [], '"errorCode":"REQUEST_LIMIT_EXCEEDED"')
            );
        })->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => '0031I0000044B1kQAE',
            ]))->setHeaders([
                CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
            ])
        )->getHeaders();

        $this->assertEquals([
            'pf-result-code'   => 1004,
            'pf-cm-event-type' => 'eventUnsubscribe',
        ], $headers);
    }

    /**
     *
     */
    public function testProcessActionWithException(): void
    {
        $this->expectException(CurlException::class);
        $this->expectExceptionCode(CurlException::REQUEST_FAILED);

        $this->getConnectorMock(
            function (RequestDto $dto, array $options = []): void {
                $this->assertEquals(
                    new Uri('https://na73.salesforce.com/services/data/v40.0/sobjects/Contact/id/0031I0000044B1kQAE'),
                    $dto->getUri()
                );

                throw new CurlException('', CurlException::REQUEST_FAILED, NULL, new Response(401));
            },
            function (string $type, array $content): void {
                $this->assertEquals('access_expiration', $type);
                $this->assertEquals([
                    'notification_type' => 'access_expiration',
                    'guid'              => 'User',
                    'token'             => 'Token',
                    'system_key'        => 'salesforce',
                    'system_name'       => 'Salesforce',
                ], $content);
            }
        )->processAction(
            (new ProcessDto())->setData(Json::encode([
                CleverFieldsEnum::FOREIGN_ID => '0031I0000044B1kQAE',
            ]))->setHeaders([
                CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
            ])
        );
    }

    /**
     * @param callable      $curlCallback
     * @param callable|NULL $loggerCallback
     *
     * @return SalesforceUpdateContactConnector
     */
    private function getConnectorMock(
        callable $curlCallback,
        ?callable $loggerCallback = NULL
    ): SalesforceUpdateContactConnector
    {
        $dto = (new RequestDto('POST', new Uri('https://na73.salesforce.com')))
            ->setHeaders([
                'Authorization' => 'Bearer 00D1I000001WyE7!ARAAQCPw1z3hchprOA9t08aqFqrY4RdcjNaEmRBHf170davnludWxhbo4WjBgWptw9OSk1yi1c4lfZm5RVo8h9sNsoGEysPd',
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);

        /** @var SalesforceSystem|MockObject $system */
        $system = $this->createPartialMock(SalesforceSystem::class, ['getRequestDto']);
        $system->method('getRequestDto')->willReturn($dto);

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

        /** @var CurlManagerInterface|MockObject $curlManager */
        $curlManager = $this->createMock(CurlManagerInterface::class);
        $curlManager->method('send')->willReturnCallback($curlCallback);

        $connector = new SalesforceUpdateContactConnector($system, $documentManager, $curlManager);

        if ($loggerCallback) {
            /** @var LoggerInterface|MockObject $logger */
            $logger = $this->createMock(LoggerInterface::class);
            $logger->method('info')->willReturnCallback($loggerCallback);
            $connector->setLogger($logger);
        }

        return $connector;
    }

}