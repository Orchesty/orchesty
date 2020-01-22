<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use OAuthException;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use PipesPhpSdkTests\Integration\Command\NullOAuth1Application;
use ReflectionException;

/**
 * Class OAuth1ApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
final class OAuth1ApplicationAbstractTest extends DatabaseTestCaseAbstract
{

    /**
     * @var NullOAuth1Application
     */
    private $testApp;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::getAuthorizationType
     */
    public function testGetType(): void
    {
        self::assertEquals('oauth', $this->testApp->getAuthorizationType());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::isAuthorized
     *
     * @throws DateTimeException
     */
    public function testIsAuthorized(): void
    {
        $applicationInstall = $this->createApplicationInstall([ApplicationInterface::AUTHORIZATION_SETTINGS => [OAuth1ApplicationInterface::TOKEN => '__token__']]);
        self::assertTrue($this->testApp->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::getApplicationForm
     *
     * @throws ApplicationInstallException
     * @throws DateTimeException
     */
    public function testGetApplicationForm(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        self::assertEquals(4, count($this->testApp->getApplicationForm($applicationInstall)));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::createDto
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::saveOauthStuff
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::authorize
     *
     * @throws DateTimeException
     * @throws AuthorizationException
     * @throws OAuthException
     */
    public function testAuthorize(): void
    {
        $applicationInstall = $this->createApplicationInstall(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS => [
                    OAuth1ApplicationInterface::CONSUMER_KEY    => 'key',
                    OAuth1ApplicationInterface::CONSUMER_SECRET => 'secret',
                ],
            ]
        );

        $provider = $this->createPartialMock(OAuth1Provider::class, ['authorize']);
        $provider->expects(self::any())->method('authorize');

        $application = new NullOAuth1Application($provider);
        $application->authorize($applicationInstall);

        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::createDto
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::setAuthorizationToken
     *
     * @throws AuthorizationException
     * @throws DateTimeException
     * @throws OAuthException
     */
    public function testSetAuthorizationToken(): void
    {
        $token              = [
            'access_token' => '__token__',
            'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
        ];
        $applicationInstall = $this->createApplicationInstall();

        $provider = $this->createPartialMock(OAuth1Provider::class, ['getAccessToken']);
        $provider->expects(self::any())->method('getAccessToken')->willReturn($token);

        $application = new NullOAuth1Application($provider);
        $application->setAuthorizationToken($applicationInstall, $token);

        self::assertEquals(
            '__token__',
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][ApplicationInterface::TOKEN]['access_token']
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::setFrontendRedirectUrl
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::getFrontendRedirectUrl
     *
     * @throws DateTimeException
     */
    public function testGetAndSetFrontendRedirectUrl(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $this->testApp->setFrontendRedirectUrl($applicationInstall, '/redirect/url');

        self::assertEquals('/redirect/url', $this->testApp->getFrontendRedirectUrl($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::saveOauthStuff
     *
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testSaveOauthStuff(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $callable           = $this->invokeMethod($this->testApp, 'saveOauthStuff');
        $dto                = new OAuth1Dto($applicationInstall);
        $callable($this->dm, $dto, ['data']);

        self::assertEquals(
            ['data'],
            $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_SETTINGS][OAuth1ApplicationInterface::OAUTH]
        );
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testApp = self::$container->get('hbpf.application.null3');
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
