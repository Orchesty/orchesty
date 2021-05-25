<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FlexiBeeApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee
 */
final class FlexiBeeApplicationTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {

        self::assertEquals('flexibee', $this->getApp()->getKey());
    }

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        self::assertEquals('FlexiBee Application', $this->getApp()->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetDescription(): void
    {
        self::assertEquals('FlexiBee Application', $this->getApp()->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testGetAuthorizationType(): void
    {
        self::assertEquals(AuthorizationTypeEnum::BASIC, $this->getApp()->getAuthorizationType());
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $this->setApp();
        $dto = $this->getApp()->getRequestDto(
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoWithHttpAuth(): void
    {
        $this->setApp();
        $dto = $this->getApp()->getRequestDto(
            $this->getAppInstall(TRUE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutUrl(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionMessage('There is no flexibee url');
        self::expectExceptionCode(3_003);

        $this->setApp();
        $this->getApp()->getRequestDto(
            $this->getAppInstall(FALSE, FALSE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutUser(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionMessage('User is not authenticated');
        self::expectExceptionCode(ApplicationInstallException::INVALID_FIELD_TYPE);

        $this->setApp();
        $this->getApp()->getRequestDto(
            $this->getAppInstall(FALSE, FALSE, FALSE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutPassword(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionMessage('User is not authenticated');
        self::expectExceptionCode(ApplicationInstallException::INVALID_FIELD_TYPE);

        $this->setApp();
        $this->getApp()->getRequestDto(
            $this->getAppInstall(FALSE, FALSE, TRUE, FALSE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutTokenParameter(): void
    {
        $this->setApp();
        $dto = $this->getApp()->getRequestDto(
            $this->getAppInstall(FALSE, TRUE, TRUE, TRUE, TRUE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoIncorrectResponse(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage(FlexiBeeApplication::INCORECT_RESPONSE);

        $this->setApp(400);
        $dto = $this->getApp()->getRequestDto(
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoEmptyBody(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage(FlexiBeeApplication::CANNOT_GET_BODY);

        $this->setApp(200, FALSE);
        $dto = $this->getApp()->getRequestDto(
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
    }

    /**
     * @throws MongoDBException
     * @throws CurlException
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutSuccess(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage(FlexiBeeApplication::TOKEN_NOT_SUCCESS);

        $this->setApp(200, TRUE, FALSE);
        $this->getApp()->getRequestDto(
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication::getSettingsForm
     *
     * @throws Exception
     */
    public function testGetSettingsForm(): void
    {
        $form = $this->getApp()->getSettingsForm();
        self::assertCount(4, $form->getFields());
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        self::assertTrue($this->getApp()->isAuthorized($this->getAppInstall()));
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication::isAuthorized
     *
     * @throws Exception
     */
    public function testIsNotAuthorize(): void
    {
        self::assertNotTrue($this->getApp()->isAuthorized(new ApplicationInstall()));
    }

    /**
     * @param int  $errorCode
     * @param bool $withBody
     * @param bool $isTokenSuccess
     * @param bool $withoutTokenParam
     */
    protected function setApp(
        int $errorCode = 200,
        bool $withBody = TRUE,
        bool $isTokenSuccess = TRUE,
        bool $withoutTokenParam = FALSE,
    ): void
    {
        self::$container->set(
            'hbpf.transport.curl_manager',
            $this->createCurlManagerMock($errorCode, $withBody, $isTokenSuccess, $withoutTokenParam),
        );
    }

    /**
     * @param int  $errorCode
     * @param bool $withBody
     * @param bool $isTokenSuccess
     * @param bool $withoutTokenParam
     *
     * @return MockObject
     */
    private function createCurlManagerMock(
        int $errorCode = 200,
        bool $withBody = TRUE,
        bool $isTokenSuccess = TRUE,
        bool $withoutTokenParam = FALSE,
    ): MockObject
    {
        if ($withBody) {
            if ($isTokenSuccess)
                $body = '{"success":"ok","authSessionId":"123","refreshToken":"token123","csrfToken":"csrfToken123"}';
            else if ($withoutTokenParam)
                $body = '{"success":"","authSessionId":"123","refreshToken":"token123","csrfToken":"csrfToken123"}';
            else
                $body = '{"success":"","authSessionId":"","refreshToken":"token123","csrfToken":"csrfToken123"}';
        } else
            $body = '';

        $curlManagerMock = self::createMock(CurlManager::class);
        $curlManagerMock->method('send')->willReturn(
            new ResponseDto(
                $errorCode,
                '',
                $body,
                [],
            ),
        );

        return $curlManagerMock;
    }

    /**
     * @return FlexiBeeApplication
     */
    private function getApp(): FlexiBeeApplication
    {
        return self::$container->get('hbpf.application.flexibee');
    }

    /**
     * @param bool $httpAuth
     * @param bool $flexiBeeUrl
     * @param bool $withUser
     * @param bool $withPassword
     * @param bool $fillToken
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function getAppInstall(
        bool $httpAuth = FALSE,
        bool $flexiBeeUrl = TRUE,
        bool $withUser = TRUE,
        bool $withPassword = TRUE,
        bool $fillToken = FALSE,
    ): ApplicationInstall
    {
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getKey());

        if ($httpAuth) {
            $auth = 'http';
        } else {
            $auth = 'json';
        }

        $arrayAuthSetttings = [];

        if ($withUser)
            $arrayAuthSetttings = array_merge($arrayAuthSetttings, ['user' => 'user123']);

        if ($withPassword)
            $arrayAuthSetttings = array_merge($arrayAuthSetttings, ['password' => 'pass123']);

        if ($flexiBeeUrl) {
            $appInstall->addSettings(
                [
                    FlexiBeeApplication::AUTHORIZATION_SETTINGS => $arrayAuthSetttings,
                    BasicApplicationAbstract::FORM =>
                    [
                        'auth'                                      => $auth,
                        'flexibeeUrl'                               => 'https://demo.flexibee.eu/c/demo',
                    ],

                ],
            );
        } else {
            $appInstall->addSettings(
                [
                    FlexiBeeApplication::AUTHORIZATION_SETTINGS => $arrayAuthSetttings,
                    BasicApplicationAbstract::FORM =>
                        [
                            'auth'                                      => $auth,
                        ],
                ],
            );
        }

        if ($fillToken) {
            $arrayClientSettings = [
                FlexiBeeApplication::AUTH_SESSION_ID => 'sessionID123',
                FlexiBeeApplication::TOKEN_GET       => 'tokenGet123',
            ];
            $appInstall->addSettings([FlexiBeeApplication::CLIENT_SETTINGS => $arrayClientSettings]);
        }

        $this->pfd($appInstall);

        return $appInstall;
    }

}
