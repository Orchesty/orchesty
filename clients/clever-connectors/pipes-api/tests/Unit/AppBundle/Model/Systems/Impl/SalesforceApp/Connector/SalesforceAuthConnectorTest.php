<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAuthConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\SalesforceAppSystem;
use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class SalesforceAuthConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesforceApp\Connector
 */
final class SalesforceAuthConnectorTest extends TestCase
{

    /**
     * @var SalesforceAuthConnector|null
     */
    private $connector = NULL;

    /**
     * @var SystemInstall
     */
    private $systemInstall;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->connector) {
            /** @var CurlManager|MockObject $curlManager */
            $curlManager = $this->getMockBuilder(CurlManager::class)->disableOriginalConstructor()->getMock();
            $curlManager->method('send')->willReturn(TRUE);

            $this->connector = new SalesforceAuthConnector($curlManager);

            $this->systemInstall = new SystemInstall();
            $this->systemInstall
                ->setUser('user123')
                ->setSystem('sys123')
                ->setToken('tok123');
        }
    }

    /**
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $this->expectException(ConnectorException::class);
        $this->expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $this->connector->processEvent(new ProcessDto());
    }

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->expectException(ConnectorException::class);
        $this->expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION);
        $this->connector->processAction(new ProcessDto());
    }

    /**
     * @throws Exception
     */
    public function testSendAuthorizeConfirm(): void
    {
        /** @var CurlManager|MockObject $curlManager */
        $curlManager = $this->getMockBuilder(CurlManager::class)->disableOriginalConstructor()->getMock();
        $curlManager->method('send')->willReturn(new ResponseDto(200, '', '', []));

        /** @var SalesforceAppSystem|MockObject $system */
        $system = $this->getMockBuilder(SalesforceAppSystem::class)->disableOriginalConstructor()->getMock();
        $system->method('getRequestDto')->willReturn(new RequestDto(CurlManager::METHOD_POST, new Uri()));

        $this->connector = new SalesforceAuthConnector($curlManager);

        $this->connector->sendAuthorizeConfirm($this->systemInstall, $system);
    }

    /**
     * @throws Exception
     */
    public function testSendAuthorizeConfirmFailed(): void
    {
        /** @var CurlManager|MockObject $curlManager */
        $curlManager = $this->getMockBuilder(CurlManager::class)->disableOriginalConstructor()->getMock();
        $curlManager->method('send')->willThrowException(new Exception());

        /** @var SalesforceAppSystem|MockObject $system */
        $system = $this->getMockBuilder(SalesforceAppSystem::class)->disableOriginalConstructor()->getMock();
        $system->method('getRequestDto')->willReturn(new RequestDto(CurlManager::METHOD_POST, new Uri()));

        $this->connector = new SalesforceAuthConnector($curlManager);

        $this->expectException(ConnectorException::class);
        $this->connector->sendAuthorizeConfirm($this->systemInstall, $system);
    }

}