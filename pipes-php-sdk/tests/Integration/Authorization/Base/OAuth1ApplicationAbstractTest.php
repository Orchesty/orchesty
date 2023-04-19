<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Authorization\Base;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\Integration\Command\NullOAuth1Application;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class OAuth1ApplicationAbstractTest
 *
 * @package PipesPhpSdkTests\Integration\Authorization\Base
 */
final class OAuth1ApplicationAbstractTest extends KernelTestCaseAbstract
{

    /**
     * @var NullOAuth1Application
     */
    private NullOAuth1Application $testApp;

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::getAuthorizationType
     * @throws Exception
     */
    public function testGetType(): void
    {
        $this->privateSetUp();
        self::assertEquals('oauth', $this->testApp->getAuthorizationType());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::isAuthorized
     *
     * @throws Exception
     */
    public function testIsAuthorized(): void
    {
        $this->privateSetUp();
        $applicationInstall = new ApplicationInstall(
            [
                'settings' => [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::TOKEN => '__token__']],
            ],
        );
        self::assertTrue($this->testApp->isAuthorized($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::getApplicationForms
     *
     * @throws Exception
     */
    public function testGetApplicationForm(): void
    {
        $this->privateSetUp();
        $applicationInstall = new ApplicationInstall();
        self::assertEquals(
            4,
            count(
                $this->testApp->getApplicationForms(
                    $applicationInstall,
                )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::FIELDS],
            ),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::createDto
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::saveOauthStuff
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::authorize
     *
     * @throws Exception
     */
    public function testAuthorize(): void
    {
        $applicationInstall = new ApplicationInstall(
            [
                'settings' => [
                    ApplicationInterface::AUTHORIZATION_FORM => [
                        OAuth1ApplicationInterface::CONSUMER_KEY    => 'key',
                        OAuth1ApplicationInterface::CONSUMER_SECRET => 'secret',
                    ],
                ],
            ],
        );

        $provider = $this->createPartialMock(OAuth1Provider::class, ['authorize']);
        $provider->expects(self::any())->method('authorize');

        $application = new NullOAuth1Application($provider);
        $application->authorize($applicationInstall);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::createDto
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::setAuthorizationToken
     *
     * @throws Exception
     */
    public function testSetAuthorizationToken(): void
    {
        $token              = [
            'access_token' => '__token__',
            'expires_in'   => DateTimeUtils::getUtcDateTime('tomorrow')->getTimestamp(),
        ];
        $applicationInstall = new ApplicationInstall();

        $provider = $this->createPartialMock(OAuth1Provider::class, ['getAccessToken']);
        $provider->expects(self::any())->method('getAccessToken')->willReturn($token);

        $application = new NullOAuth1Application($provider);
        $application->setAuthorizationToken($applicationInstall, $token);

        self::assertEquals(
            '__token__',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][ApplicationInterface::TOKEN][OAuth2Provider::ACCESS_TOKEN],
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::setFrontendRedirectUrl
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::getFrontendRedirectUrl
     *
     * @throws Exception
     */
    public function testGetAndSetFrontendRedirectUrl(): void
    {
        $this->privateSetUp();
        $applicationInstall = new ApplicationInstall();
        $this->testApp->setFrontendRedirectUrl($applicationInstall, '/redirect/url');

        self::assertEquals('/redirect/url', $this->testApp->getFrontendRedirectUrl($applicationInstall));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationAbstract::saveOauthStuff
     *
     * @throws Exception
     */
    public function testSaveOauthStuff(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);
        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":null,"name":null,"nonEncryptedSettings":[],"encryptedSettings":"001_e\/JlNGWNzxhpMluJq6AIXqSwIGG\/kwy7UCMcQxLRg4s=:nUgeUUcCdDtl+VjR+HuO8\/YQcLVqysMzLPVogoPea14=:2PhXCdXiAaYy+EA8dptzNimqa6hkMB3x:fhdQJm5XryXFfIWfXpN894nThpk1eDLsElHN7NDAfFJqZV62kUN0E83M\/cte\/0GIfeX39nD4AHBhctVQW2rsDhfCLY83DUJgMCH95yjH83I4zWjbvFj5","settings":[],"created":"2023-02-13 13:58:17","updated":"2023-02-13 13:58:17","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[]'),
                [
                    'created'           => '2023-02-13 13:58:17',
                    'encryptedSettings' => '001_e/JlNGWNzxhpMluJq6AIXqSwIGG/kwy7UCMcQxLRg4s=:nUgeUUcCdDtl+VjR+HuO8/YQcLVqysMzLPVogoPea14=:2PhXCdXiAaYy+EA8dptzNimqa6hkMB3x:fhdQJm5XryXFfIWfXpN894nThpk1eDLsElHN7NDAfFJqZV62kUN0E83M/cte/0GIfeX39nD4AHBhctVQW2rsDhfCLY83DUJgMCH95yjH83I4zWjbvFj5',
                    'updated'           => '2023-02-13 13:58:17',
                ],
            ),
        );

        $this->privateSetUp();
        $applicationInstall = new ApplicationInstall();
        $callable           = $this->invokeMethod($this->testApp, 'saveOauthStuff');
        $dto                = new OAuth1Dto($applicationInstall);
        /** @var ApplicationInstallRepository $applicationInstallRepository */
        $applicationInstallRepository = self::getContainer()->get('hbpf.application_install.repository');
        $callable($applicationInstallRepository, $dto, ['data']);

        self::assertEquals(
            ['data'],
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_FORM][OAuth1ApplicationInterface::OAUTH],
        );
    }

    /**
     * @throws Exception
     */
    protected function privateSetUp(): void
    {
        $this->testApp = self::getContainer()->get('hbpf.application.null3');
    }

}
