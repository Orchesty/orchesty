<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\TestOAuth2NullApplication;
use PipesPhpSdkTests\Integration\Command\NullOAuth2Application;
use ReflectionException;

/**
 * Class OAuth2ApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
final class OAuth2ApplicationAbstractTest extends DatabaseTestCaseAbstract
{

    /**
     * @var TestOAuth2NullApplication
     */
    private $testApp;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::getAuthorizationType
     */
    public function testGetAuthorizationType(): void
    {
        self::assertEquals('oauth2', $this->testApp->getAuthorizationType());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::isAuthorized
     *
     * @throws DateTimeException
     */
    public function testIsAuthorize(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [
                ApplicationInterface::AUTHORIZATION_SETTINGS =>
                    [
                        OAuth2ApplicationInterface::TOKEN =>
                            [
                                OAuth2Provider::ACCESS_TOKEN => 'access_token',
                            ],
                    ],
            ]
        );

        self::assertTrue($this->testApp->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::getApplicationForm
     * @throws DateTimeException
     * @throws ApplicationInstallException
     */
    public function testGetApplicationForm(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        self::assertEquals(3, count($this->testApp->getApplicationForm($applicationInstall)));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::getTokens
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::refreshAuthorization
     *
     * @throws DateTimeException
     * @throws AuthorizationException
     */
    public function testRefreshAuthorization(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS => [
                    OAuth2ApplicationInterface::TOKEN => [
                        'access_token' => '123',
                        'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                    ],
                ],
            ]
        );
        $provider           = self::createPartialMock(OAuth2Provider::class, ['refreshAccessToken']);
        $provider
            ->expects(self::any())
            ->method('refreshAccessToken')
            ->willReturn(
                [
                    OAuth2Provider::EXPIRES      => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                    OAuth2Provider::ACCESS_TOKEN => '__token__',
                ]
            );

        $application        = new NullOAuth2Application($provider);
        $applicationInstall = $application->refreshAuthorization($applicationInstall);

        self::assertEquals(
            '__token__',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN]
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::getFrontendRedirectUrl
     *
     * @throws DateTimeException
     */
    public function testGetFrontendRedirectUrl(): void
    {
        $applicationInstall = $this->createApplicationInstall([ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::REDIRECT_URL => '/redirect/url']]);
        self::assertEquals('/redirect/url', $this->testApp->getFrontendRedirectUrl($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::setAuthorizationToken
     *
     * @throws DateTimeException
     * @throws AuthorizationException
     */
    public function testAuthorizationToken(): void
    {
        $applicationInstall = $this->createApplicationInstall();

        $provider = self::createPartialMock(OAuth2Provider::class, ['getAccessToken']);
        $provider
            ->expects(self::any())
            ->method('getAccessToken')
            ->willReturn(
                [
                    OAuth2Provider::EXPIRES      => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
                    OAuth2Provider::ACCESS_TOKEN => '__token__',
                ]
            );
        $application = new NullOAuth2Application($provider);
        $application->setAuthorizationToken($applicationInstall, ['code' => '__code__']);

        self::assertEquals(
            '__token__',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN]
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::getAccessToken
     *
     * @throws DateTimeException
     * @throws ApplicationInstallException
     */
    public function testGetAccessToken(): void
    {
        $applicationInstall = $this->createApplicationInstall([ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::TOKEN => [OAuth2Provider::ACCESS_TOKEN => '__token__']]]);

        self::assertEquals('__token__', $this->testApp->getAccessToken($applicationInstall));

        $applicationInstall = $this->createApplicationInstall();
        self::expectException(ApplicationInstallException::class);
        $this->testApp->getAccessToken($applicationInstall);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::setApplicationSettings
     *
     * @throws DateTimeException
     */
    public function testSetApplicationSettings(): void
    {
        $applicationInstall = $this->createApplicationInstall();

        $this->testApp->setApplicationSettings(
            $applicationInstall,
            [
                OAuth2ApplicationInterface::CLIENT_ID     => '123',
                OAuth2ApplicationInterface::CLIENT_SECRET => '__secret__',
            ]
        );

        self::assertEquals(
            '123',
            $applicationInstall->getSettings()[ApplicationAbstract::FORM][OAuth2ApplicationInterface::CLIENT_ID]
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::createDto
     *
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testCreateDto(): void
    {
        $applicationInstall = $this->createApplicationInstall();
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

        $this->testApp = self::$container->get('hbpf.application.null2');
    }

    /**
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     * @throws DateTimeException
     */
    private function createApplicationInstall(array $settings = []): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey('null1')
            ->setUser('user')
            ->setSettings($settings);

        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
