<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use OAuth;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use TypeError;

/**
 * Class AuthorizeUserCommandTest
 *
 * @package PipesPhpSdkTests\Integration\Command
 */
final class AuthorizeUserCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader
     * @covers \Hanaboso\PipesPhpSdk\Application\Loader\ApplicationLoader::getApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::authorizeApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Utils\ApplicationUtils::generateUrl
     * @covers \Hanaboso\PipesPhpSdk\Application\Repository\ApplicationInstallRepository::findUserApp
     * @covers \Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand
     * @covers \Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand::execute
     * @covers \Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand::getHelper
     * @covers \Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand::configure
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract::authorize
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider::createClient
     * @throws Exception
     */
    public function testExecuteOauth2(): void
    {
        $application   = new Application(self::$kernel);
        $command       = $application->get('user:authorize');
        $commandTester = new CommandTester($command);

        $app = (new ApplicationInstall())
            ->setKey('null2')
            ->setUser('user');
        $this->pfd($app);
        $this->dm->clear();

        $commandTester->setInputs(['null2', 'user']);
        ob_start();
        $commandTester->execute(['command' => $command->getName(), '--env' => 'oauthconsole']);
        $content = ob_get_clean();

        self::assertStringContainsString('', (string) $content);
    }

    /**
     * @throws Exception
     */
    public function testExecuteOauth1(): void
    {
        putenv('APP_ENV=oauthconsole');

        $app = (new ApplicationInstall())
            ->setKey('null1')
            ->setUser('user')
            ->setSettings(
                [
                    ApplicationInterface::AUTHORIZATION_FORM => [
                        OAuth1ApplicationInterface::CONSUMER_KEY => 'consumer.key',
                        OAuth1ApplicationInterface::TOKEN        => 'secret.key',
                    ],
                ],
            );
        $this->pfd($app);

        $install  = new ApplicationInstall();
        $provider = $this->getMockedProvider();
        $dto      = new OAuth1Dto($install);

        $provider->authorize(
            $dto,
            'token/url',
            'authorize/url',
            static function (): void {
            },
            [],
        );

        $this->expectOutputString('authorize/url?oauth_callback=127.0.0.4&oauth_token=aabbcc');
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand::execute
     */
    public function testExecuteMissingEnvParam(): void
    {
        $application   = new Application(self::$kernel);
        $command       = $application->get('user:authorize');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute(['command' => $command->getName(),]);

        self::assertStringContainsString(
            'Please make sure that your env is set to --env=oauthconsole.',
            $commandTester->getDisplay(),
        );
        self::assertEquals(1, $exitCode);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Command\AuthorizeUserCommand::execute
     */
    public function testExecuteMissingUserParam(): void
    {
        $application   = new Application(self::$kernel);
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
        $dm = self::createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('flush')->willReturn(TRUE);

        $redirect = self::createMock(RedirectInterface::class);
        $this->expectException(TypeError::class);

        $oauth = self::createPartialMock(OAuth::class, ['getRequestToken']);
        $oauth->method('getRequestToken')->willReturn(['oauth_token' => 'aabbcc', 'oauth_token_secret' => '112233']);

        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$dm, $redirect])
            ->onlyMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
