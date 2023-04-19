<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector\FlexiBeeGetContactsArrayConnector;
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
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class FlexiBeeGetContactsArrayConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeGetContactsArrayConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector\FlexiBeeGetContactsArrayConnector::getName
     *
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals(
            'flexibee.get-contacts-array',
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
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($this->getAppInstall()->toArray())),
            ),
        );

        $body = '{"success":"ok","authSessionId":"123","refreshToken":"token123","csrfToken":"csrfToken123"}';

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
     * @throws GuzzleException
     * @throws OnRepeatException
     * @throws ApplicationInstallException
     * @throws CustomNodeException
     * @throws DateTimeException
     * @throws PipesFrameworkException
     */
    public function testProcessActionRequestException(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);

        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["flexibee"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode($this->getAppInstall()->toArray())),
            ),
        );

        $body = '{"success":"ok","authSessionId":"123","refreshToken":"token123","csrfToken":"csrfToken123"}';

        $dto = DataProvider::getProcessDto(
            $this->getApp()->getName(),
            'user',
            $body,
        );

        self::expectException(OnRepeatException::class);
        $this
            ->createConnector(DataProvider::createResponseDto(), new CurlException())
            ->setApplication($this->getApp())
            ->processAction($dto);
    }

    /**
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
     * @return FlexiBeeGetContactsArrayConnector
     */
    private function createConnector(ResponseDto $dto, ?Exception $exception = NULL): FlexiBeeGetContactsArrayConnector
    {
        $sender = self::createMock(CurlManager::class);

        if ($exception) {
            $sender->method('send')->willThrowException($exception);
        } else {
            $sender->method('send')->willReturn($dto);
        }

        $flexiBeeGetContactsArrayConnector = new FlexiBeeGetContactsArrayConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $flexiBeeGetContactsArrayConnector
            ->setSender($sender);

        return $flexiBeeGetContactsArrayConnector;
    }

}
