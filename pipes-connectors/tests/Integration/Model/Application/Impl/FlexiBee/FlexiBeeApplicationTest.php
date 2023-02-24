<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockServer\Mock;
use HbPFConnectorsTests\MockServer\MockServer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class FlexiBeeApplicationTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\FlexiBee
 */
final class FlexiBeeApplicationTest extends KernelTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetKey(): void
    {

        self::assertEquals('flexibee', $this->getApp()->getName());
    }

    /**
     * @throws Exception
     */
    public function testGetPublicName(): void
    {
        self::assertEquals('FlexiBee Application', $this->getApp()->getPublicName());
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
        self::assertEquals(AuthorizationTypeEnum::BASIC->value, $this->getApp()->getAuthorizationType());
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetRequestDto(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $this->setApp();

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"user","name":"flexibee","nonEncryptedSettings":[],"encryptedSettings":"001_FlnM4agF7XW43FfvLS3rCJl8nGu9b7I8Dy63UaP6D2k=:20E8SbaXH\/JUGpp+7C4fxTQkOp9RvZ+ev6uaKfuOWzU=:xNX4LIh6HKFk7e2xKyTj+BM4nNXBWhF6:mxeDUNj08GJOb0nrTFJMTuH+8ZkkHGBj\/puzSvCxkxNSE7G5nA8y8+NXxFcWYORVlVvmY5kMxTk1JXvkBcQTQL0pHj2Bjgs8OExG3IS7kW\/9DJ7J5EsM1hkbZBJLr2voPa7HTec8aFoTL1w0YifVUf5y5cT1cwJBJ2V4tEcbHZSmitnvhSoh4Hba9VcbVlQ7x1G8gvVGYLQrmaCtMVhpAXXEn1k\/5DFrkZ50ZYrKGKD63FvKjL0fXKrgAoeobaCcr+YwEvKeGgUzr3G3QD2ZKHBkC16P901hEtjRP6pSS87gBC7+3IwUs23a0bZftIer5qn6AlPYrguL\/y2WeiLKM3XlZdFbXGgazOGoNsc2\/bXL0USk\/dsdSoGB8jHzsSGTDWiHGeZQQS5Uam+hvwcfDCXS9tY91qo4nUCBFub5nFumWeWPuVAOPgH1ezUBKwNqebUPw8ZC\/wukCzh8","settings":[],"created":"2023-02-23 09:13:56","updated":"2023-02-23 09:13:56","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], Json::encode([$this->getAppInstall()->toArray()])),
                [
                    'encryptedSettings' => '001_FlnM4agF7XW43FfvLS3rCJl8nGu9b7I8Dy63UaP6D2k=:20E8SbaXH/JUGpp+7C4fxTQkOp9RvZ+ev6uaKfuOWzU=:xNX4LIh6HKFk7e2xKyTj+BM4nNXBWhF6:mxeDUNj08GJOb0nrTFJMTuH+8ZkkHGBj/puzSvCxkxNSE7G5nA8y8+NXxFcWYORVlVvmY5kMxTk1JXvkBcQTQL0pHj2Bjgs8OExG3IS7kW/9DJ7J5EsM1hkbZBJLr2voPa7HTec8aFoTL1w0YifVUf5y5cT1cwJBJ2V4tEcbHZSmitnvhSoh4Hba9VcbVlQ7x1G8gvVGYLQrmaCtMVhpAXXEn1k/5DFrkZ50ZYrKGKD63FvKjL0fXKrgAoeobaCcr+YwEvKeGgUzr3G3QD2ZKHBkC16P901hEtjRP6pSS87gBC7+3IwUs23a0bZftIer5qn6AlPYrguL/y2WeiLKM3XlZdFbXGgazOGoNsc2/bXL0USk/dsdSoGB8jHzsSGTDWiHGeZQQS5Uam+hvwcfDCXS9tY91qo4nUCBFub5nFumWeWPuVAOPgH1ezUBKwNqebUPw8ZC/wukCzh8',
                    'created' => '2023-02-23 09:13:56',
                    'updated' => '2023-02-23 09:13:56',
                ],
            ),
        );

        $dto = $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
            '',
            json::encode(['foo' => 'bar']),
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetRequestDtoWithHttpAuth(): void
    {
        $this->setApp();
        $dto = $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(TRUE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutUrl(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionMessage('There is no flexibee url');
        self::expectExceptionCode(3_003);

        $this->setApp();
        $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(FALSE, FALSE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutUser(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionMessage('User is not authenticated');
        self::expectExceptionCode(ApplicationInstallException::INVALID_FIELD_TYPE);

        $this->setApp();
        $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(FALSE, FALSE, FALSE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutPassword(): void
    {
        self::expectException(ApplicationInstallException::class);
        self::expectExceptionMessage('User is not authenticated');
        self::expectExceptionCode(ApplicationInstallException::INVALID_FIELD_TYPE);

        $this->setApp();
        $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(FALSE, FALSE, TRUE, FALSE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     * @throws Exception
     */
    public function testGetRequestDtoWithoutTokenParameter(): void
    {
        $this->setApp();
        $dto = $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(FALSE, TRUE, TRUE, TRUE, TRUE),
            CurlManager::METHOD_POST,
            NULL,
            json::encode(['foo' => 'bar']),
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
        self::assertEquals(Json::encode(['foo' => 'bar']), $dto->getBody());
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     */
    public function testGetRequestDtoIncorrectResponse(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage(FlexiBeeApplication::INCORECT_RESPONSE);

        $this->setApp(400);
        $dto = $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     */
    public function testGetRequestDtoEmptyBody(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage(FlexiBeeApplication::CANNOT_GET_BODY);

        $this->setApp(200, FALSE);
        $dto = $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
        );

        self::assertEquals(CurlManager::METHOD_POST, $dto->getMethod());
    }

    /**
     * @return void
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws GuzzleException
     */
    public function testGetRequestDtoWithoutSuccess(): void
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage(FlexiBeeApplication::TOKEN_NOT_SUCCESS);

        $this->setApp(200, TRUE, FALSE);
        $this->getApp()->getRequestDto(
            new ProcessDto(),
            $this->getAppInstall(),
            CurlManager::METHOD_POST,
        );
    }

    /**
     * @covers \Hanaboso\HbPFConnectors\Model\Application\Impl\FlexiBee\FlexiBeeApplication::getFormStack
     *
     * @throws Exception
     */
    public function testGetFormStack(): void
    {
        $forms = $this->getApp()->getFormStack()->getForms();
        foreach ($forms as $form) {
            self::assertCount(4, $form->getFields());
        }
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
        self::getContainer()->set(
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
        return self::getContainer()->get('hbpf.application.flexibee');
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
        $appInstall = DataProvider::getBasicAppInstall($this->getApp()->getName());

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
                    ApplicationInterface::AUTHORIZATION_FORM =>
                        [
                            ...$arrayAuthSetttings,
                            'auth'        => $auth,
                            'flexibeeUrl' => 'https://demo.flexibee.eu/c/demo',
                        ],

                ],
            );
        } else {
            $appInstall->addSettings(
                [
                    ApplicationInterface::AUTHORIZATION_FORM =>
                        [
                            ...$arrayAuthSetttings,
                            'auth' => $auth,
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

        return $appInstall;
    }

}
