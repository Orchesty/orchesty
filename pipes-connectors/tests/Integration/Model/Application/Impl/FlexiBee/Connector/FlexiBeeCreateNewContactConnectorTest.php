<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector\FlexiBeeCreateNewContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FlexiBeeCreateNewContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeCreateNewContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector\FlexiBeeCreateNewContactConnector::getId
     *
     * @throws Exception
     */
    public function testGetId(): void
    {
        self::assertEquals(
            'flexibee.create-new-contact',
            $this->createConnector(DataProvider::createResponseDto())->getId(),
        );
    }

    /**
     * @throws ConnectorException
     */
    public function testProcessEvent(): void
    {
        self::expectException(ConnectorException::class);
        self::expectExceptionCode(ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT);
        $this->createConnector(DataProvider::createResponseDto())->processEvent(DataProvider::getProcessDto());
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $body = '{"name":"HokusPokus", "country":"CZ","org-type":"PODNIKATELE+PU"}';
        $this->getAppInstall();
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getKey(),
            'user',
            $body,
        );

        $res = $this->createConnector(
            DataProvider::createResponseDto($body),
        )
            ->setApplication($this->getApp())
            ->processAction($dto);
        self::assertEquals($body, $res->getData());
    }

    /**
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionCurlException(): void
    {
        $body = '{"name":"HokusPokus", "country":"CZ","org-type":"PODNIKATELE+PU"}';
        $this->getAppInstall();
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getKey(),
            'user',
            $body,
        );

        self::expectException(OnRepeatException::class);
        $this->createConnector(
            DataProvider::createResponseDto($body),
            new CurlException(),
        )
            ->setApplication($this->getApp())
            ->processAction($dto);
    }

    /**
     * @throws ConnectorException
     */
    public function testProcessActionLegislationNotSetException(): void
    {
        $this->testProcessActionException('{"name":"HokusPokus","org-type":"PODNIKATELE+PU"}');
    }

    /**
     * @throws ConnectorException
     */
    public function testProcessActionNameNotSetException(): void
    {
        $this->testProcessActionException('{"country":"SK", "org-type":"PODNIKATELE+PU"}');
    }

    /**
     * @throws ConnectorException
     */
    public function testProcessActionLegislationNotAcceptedException(): void
    {
        $this->testProcessActionException('{"name":"HokusPokus", "country":"SK", "org-type":"PODNIKATELE+PU"}');
    }

    /**
     * @param string $body
     *
     * @throws ConnectorException
     */
    private function testProcessActionException(string $body = '{}'): void
    {
        $this->getAppInstall();
        $this->dm->clear();

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getKey(),
            'user',
            $body,
        );

        $response = $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->getApp())
            ->processAction($dto);
        self::assertArrayHasKey('pf-result-code', $response->getHeaders());
        self::assertEquals('1003', $response->getHeaders()['pf-result-code']);
    }

    /**
     * @throws Exception
     */
    private function getAppInstall(): void
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getKey());

        $appInstall->addSettings(
            [
                FlexiBeeApplication::AUTHORIZATION_SETTINGS =>
                    [
                        'user'     => 'user123',
                        'password' => 'pass123',
                    ],
                BasicApplicationAbstract::FORM                   =>
                    [
                        'auth'        => 'http',
                        'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                    ],

            ],
        );

        $appInstall->addSettings(
            [
                FlexiBeeApplication::CLIENT_SETTINGS => [
                    FlexiBeeApplication::AUTH_SESSION_ID => 'sessionID123',
                    FlexiBeeApplication::TOKEN_GET       => 'tokenGet123',
                ],
            ],
        );

        $this->pfd($appInstall);
    }

    /**
     * @return FlexiBeeApplication
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::$container->get('hbpf.application.flexibee');
    }

    /**
     * @param ResponseDto    $dto
     * @param Exception|null $exception
     *
     * @return FlexiBeeCreateNewContactConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): FlexiBeeCreateNewContactConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        return new FlexiBeeCreateNewContactConnector($this->dm, $sender);
    }

}
