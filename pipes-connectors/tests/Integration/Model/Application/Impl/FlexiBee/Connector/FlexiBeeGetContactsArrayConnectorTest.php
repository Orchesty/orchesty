<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector;

use Exception;
use Hanaboso\CommonsBundle\Exception\OnRepeatException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\Connector\FlexiBeeGetContactsArrayConnector;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class FlexiBeeGetContactsArrayConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee\Connector
 */
final class FlexiBeeGetContactsArrayConnectorTest extends DatabaseTestCaseAbstract
{

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
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $body = '{"success":"ok","authSessionId":"123","refreshToken":"token123","csrfToken":"csrfToken123"}';
        $this->getAppInstall();
        $this->dm->clear();

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
     * @throws ConnectorException
     * @throws Exception
     */
    public function testProcessActionRequestException(): void
    {
        $body = '{"success":"ok","authSessionId":"123","refreshToken":"token123","csrfToken":"csrfToken123"}';
        $this->getAppInstall();
        $this->dm->clear();

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
    private function getAppInstall(): void
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getName());

        $appInstall->addSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM =>
                    [
                        'user'     => 'user123',
                        'password' => 'pass123',
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

        $flexiBeeGetContactsArrayConnector = new FlexiBeeGetContactsArrayConnector();
        $flexiBeeGetContactsArrayConnector
            ->setSender($sender)
            ->setDb($this->dm);

        return $flexiBeeGetContactsArrayConnector;
    }

}
