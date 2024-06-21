<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\Integration\Application\TestOAuth2NullApplication;
use PipesPhpSdkTests\Integration\Command\NullOAuth2Application;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class OAuth2ApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
#[CoversClass(OAuth2ApplicationAbstract::class)]
final class OAuth2ApplicationAbstractTest extends KernelTestCaseAbstract
{

    /**
     * @var TestOAuth2NullApplication
     */
    private TestOAuth2NullApplication $testApp;

    /**
     * @return void
     */
    public function testGetAuthorizationType(): void
    {
        self::assertEquals('oauth2', $this->testApp->getAuthorizationType());
    }

    /**
     * @throws Exception
     */
    public function testIsAuthorize(): void
    {
        $applicationInstall = new ApplicationInstall(
            [
                'settings' => [
                    ApplicationInterface::AUTHORIZATION_FORM =>
                        [
                            ApplicationInterface::TOKEN =>
                                [
                                    OAuth2Provider::ACCESS_TOKEN => 'access_token',
                                ],
                        ],
                ],
            ],
        );

        self::assertTrue($this->testApp->isAuthorized($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testGetApplicationForm(): void
    {
        $applicationInstall = new ApplicationInstall();
        self::assertEquals(
            3,
            count(
                $this->testApp->getApplicationForms(
                    $applicationInstall,
                )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS],
            ),
        );
    }

    /**
     * @throws Exception
     */
    public function testRefreshAuthorization(): void
    {
        $applicationInstall = new ApplicationInstall(
            [
                'settings' => [
                    ApplicationInterface::AUTHORIZATION_FORM => [
                        ApplicationInterface::TOKEN => [
                            'access_token' => '123',
                            'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                        ],
                    ],
                ],
            ],
        );
        $provider           = self::createPartialMock(OAuth2Provider::class, ['refreshAccessToken']);
        $provider
            ->expects(self::any())
            ->method('refreshAccessToken')
            ->willReturn(
                [
                    OAuth2Provider::ACCESS_TOKEN => '__token__',
                    OAuth2Provider::EXPIRES      => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                ],
            );

        $application        = new NullOAuth2Application($provider);
        $applicationInstall = $application->refreshAuthorization($applicationInstall);

        self::assertEquals(
            '__token__',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetFrontendRedirectUrl(): void
    {
        $applicationInstall = new ApplicationInstall(
            [
                'settings' => [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::FRONTEND_REDIRECT_URL => '/redirect/url']],
            ],
        );
        self::assertEquals('/redirect/url', $this->testApp->getFrontendRedirectUrl($applicationInstall));
    }

    /**
     * @throws Exception
     */
    public function testAuthorizationToken(): void
    {
        $applicationInstall = new ApplicationInstall();

        $provider = self::createPartialMock(OAuth2Provider::class, ['getAccessToken']);
        $provider
            ->expects(self::any())
            ->method('getAccessToken')
            ->willReturn(
                [
                    OAuth2Provider::ACCESS_TOKEN => '__token__',
                    OAuth2Provider::EXPIRES      => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                ],
            );
        $application = new NullOAuth2Application($provider);
        $application->setAuthorizationToken($applicationInstall, ['code' => '__code__']);

        self::assertEquals(
            '__token__',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAccessToken(): void
    {
        $applicationInstall = new ApplicationInstall(
            [
                'settings' => [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '__token__']]],
            ],
        );

        self::assertEquals('__token__', $this->testApp->getAccessToken($applicationInstall));

        $applicationInstall = new ApplicationInstall();
        self::expectException(ApplicationInstallException::class);
        $this->testApp->getAccessToken($applicationInstall);
    }

    /**
     * @throws Exception
     */
    public function testSetApplicationSettings(): void
    {
        $applicationInstall = new ApplicationInstall();

        $this->testApp->saveApplicationForms(
            $applicationInstall,
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    OAuth2ApplicationInterface::CLIENT_ID     => '123',
                    OAuth2ApplicationInterface::CLIENT_SECRET => '__secret__',
                ],
            ],
        );

        self::assertEquals(
            '123',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][OAuth2ApplicationInterface::CLIENT_ID],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateDto(): void
    {
        $applicationInstall = new ApplicationInstall();
        /** @var OAuth2Dto $dto */
        $dto = $this->invokeMethod($this->testApp, 'createDto', [$applicationInstall, '/redirect/url']);

        self::assertEquals('/redirect/url', $dto->getRedirectUrl());
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testApp = self::getContainer()->get('hbpf.application.null2');
    }

}
