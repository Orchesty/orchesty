<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository;
use Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand;
use Hanaboso\Utils\String\Json;
use OAuth;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use TypeError;

/**
 * Class AuthorizeUserCommandTest
 *
 * @package PipesPhpSdkTests\Integration\Command
 */
#[CoversClass(ApplicationLoader::class)]
#[CoversClass(ApplicationManager::class)]
#[CoversClass(ApplicationUtils::class)]
#[CoversClass(ApplicationInstallRepository::class)]
#[CoversClass(AuthorizeUserCommand::class)]
#[CoversClass(OAuth2ApplicationAbstract::class)]
#[CoversClass(OAuth2Provider::class)]
final class AuthorizeUserCommandTest extends KernelTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @throws Exception
     */
    public function testExecuteOauth2(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null2"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], '[{"name":"null","user":"user"}]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"user","name":"null","nonEncryptedSettings":[],"encryptedSettings":"001_njvjIYXBFEyG3SN5aorqcpzWmAzDOoa2YD3yJ1E1nqk=:cOQj3xzk1PbgK7Cp5S56fLZGFnBvC3Vr94tvB2DgQO8=:+4+bYTP\/BdXDiJPrOnF4JNL9XFDWQ4eb:m5qmJyCQxXY6d1jHzu91ouU4mzwgKizyTlYG0DxbE\/rxJYf7wO8L9iyw3ka47Ut9KE2oph81Ma4qAbJP4s4K\/J51Rk2rSZMBxmyraqB5YXCbd96+m5pOexGQ","settings":[],"created":"2023-02-08 07:41:54","updated":"2023-02-08 07:41:54","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new Response(200, [], '[]'),
                ['created' => '2023-02-08 07:41:54', 'updated' => '2023-02-08 07:41:54', 'encryptedSettings' => '001_njvjIYXBFEyG3SN5aorqcpzWmAzDOoa2YD3yJ1E1nqk=:cOQj3xzk1PbgK7Cp5S56fLZGFnBvC3Vr94tvB2DgQO8=:+4+bYTP/BdXDiJPrOnF4JNL9XFDWQ4eb:m5qmJyCQxXY6d1jHzu91ouU4mzwgKizyTlYG0DxbE/rxJYf7wO8L9iyw3ka47Ut9KE2oph81Ma4qAbJP4s4K/J51Rk2rSZMBxmyraqB5YXCbd96+m5pOexGQ'],
            ),
        );
        /** @var KernelInterface $kernel */
        $kernel      = self::$kernel;
        $application = new Application($kernel);

        self::expectOutputString('');
        $command       = $application->get('user:authorize');
        $commandTester = new CommandTester($command);
        $commandTester->setInputs(['null2', 'user']);
        $commandTester->execute(['command' => $command->getName(), '--env' => 'oauthconsole']);
    }

    /**
     * @throws Exception
     */
    public function testExecuteOauth1(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        putenv('APP_ENV=oauthconsole');

        $install  = new ApplicationInstall();
        $provider = $this->getMockedProvider();
        $dto      = new OAuth1Dto($install);

        $provider->authorize(
            $dto,
            'token/url',
            'authorize/url',
            static function (): void {
            },
        );

        $this->expectOutputString('authorize/url?oauth_callback=127.0.0.4&oauth_token=aabbcc');
    }

    /**
     * @return void
     */
    public function testExecuteMissingEnvParam(): void
    {
        /** @var KernelInterface $kernel */
        $kernel        = self::$kernel;
        $application   = new Application($kernel);
        $command       = $application->get('user:authorize');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['command' => $command->getName(),]);

        self::assertStringContainsString(
            'Please make sure that your env is set to --env=oauthconsole.',
            $commandTester->getDisplay(),
        );
        self::assertSame(1, $exitCode);
    }

    /**
     * @return void
     */
    public function testExecuteMissingUserParam(): void
    {
        /** @var KernelInterface $kernel */
        $kernel        = self::$kernel;
        $application   = new Application($kernel);
        $command       = $application->get('user:authorize');
        $commandTester = new CommandTester($command);

        $commandTester->setInputs(['null2', '']);
        $commandTester->execute(['command' => $command->getName(), '--env' => 'oauthconsole']);

        self::assertStringContainsString(
            'Please make sure that input parameters are string.',
            $commandTester->getDisplay(),
        );
    }

    /**
     * @return OAuth1Provider
     * @throws Exception
     */
    private function getMockedProvider(): OAuth1Provider
    {
        $redirect = self::createMock(RedirectInterface::class);
        $this->expectException(TypeError::class);

        $oauth = self::createPartialMock(OAuth::class, ['getRequestToken']);
        $oauth->method('getRequestToken')->willReturn(['oauth_token' => 'aabbcc', 'oauth_token_secret' => '112233']);

        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$redirect])
            ->onlyMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
