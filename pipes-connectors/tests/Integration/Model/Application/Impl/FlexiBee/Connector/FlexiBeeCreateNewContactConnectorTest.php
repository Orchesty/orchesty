<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector\FlexiBeeCreateNewContactConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\CustomNode\Exception\CustomNodeException;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FlexiBeeCreateNewContactConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector
 */
#[CoversClass(FlexiBeeCreateNewContactConnector::class)]
final class FlexiBeeCreateNewContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals(
            'flexibee.create-new-contact',
            $this->createConnector(DataProvider::createResponseDto())->getName(),
        );
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->getAppInstall()->toArray()])),
            ),
        );

        $body = '{"name":"HokusPokus", "country":"CZ","org-type":"PODNIKATELE+PU"}';

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getName(),
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
     * @return void
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     * @throws Exception
     */
    public function testProcessActionCurlException(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$this->getAppInstall()->toArray()])),
            ),
        );

        $body = '{"name":"HokusPokus", "country":"CZ","org-type":"PODNIKATELE+PU"}';
        $this->getAppInstall();

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getName(),
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
     * @return void
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     */
    public function testProcessActionLegislationNotSetException(): void
    {
        $this->testProcessActionException('{"name":"HokusPokus","org-type":"PODNIKATELE+PU"}');
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     */
    public function testProcessActionNameNotSetException(): void
    {
        $this->testProcessActionException('{"country":"SK", "org-type":"PODNIKATELE+PU"}');
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     */
    public function testProcessActionLegislationNotAcceptedException(): void
    {
        $this->testProcessActionException('{"name":"HokusPokus", "country":"SK", "org-type":"PODNIKATELE+PU"}');
    }

    /**
     * @param string $body
     *
     * @return void
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws PipesFrameworkException
     * @throws Exception
     */
    private function testProcessActionException(string $body = '{}'): void
    {
        $this->getAppInstall();

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getName(),
            'user',
            $body,
        );

        $response = $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->getApp())
            ->processAction($dto);
        self::assertArrayHasKey('result-code', $response->getHeaders());
        self::assertEquals('1003', $response->getHeaders()['result-code']);
    }

    /**
     * @return ApplicationInstall
     * @throws Exception
     */
    private function getAppInstall(): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getName());

        $appInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        'auth'        => 'http',
                        'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                        'password'    => 'pass123',
                        'user'        => 'user123',
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

        return $appInstall;
    }

    /**
     * @return FlexiBeeApplication
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::getContainer()->get('hbpf.application.flexibee');
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

        $flexibeeCreateNewContact = new FlexiBeeCreateNewContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $flexibeeCreateNewContact
            ->setSender($sender);

        return $flexibeeCreateNewContact;
    }

}
