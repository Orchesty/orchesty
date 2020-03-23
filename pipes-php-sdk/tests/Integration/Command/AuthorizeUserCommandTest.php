<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use Hanaboso\PipesPhpSdk\Command\RedirectCommand;
use OAuth;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @covers \Hanaboso\PipesPhpSdk\Command\RedirectCommand::make
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

        $redirect = $this->createPartialMock(RedirectCommand::class, ['make']);
        $redirect->expects(self::any())->method('make');
        self::$container->set('hbpf.redirect', $redirect);

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
                    BasicApplicationInterface::AUTHORIZATION_SETTINGS => [
                        OAuth1ApplicationInterface::CONSUMER_KEY => 'consumer.key',
                        OAuth1ApplicationInterface::TOKEN        => 'secret.key',
                    ],
                ]
            );
        $this->pfd($app);

        $install = new ApplicationInstall();
        /** @var OAuth1Provider|MockObject $provider */
        $provider = $this->getMockedProvider(['oauth_token' => 'aabbcc', 'oauth_token_secret' => '112233']);
        $dto      = new OAuth1Dto($install);

        $provider->authorize(
            $dto,
            'token/url',
            'authorize/url',
            static function (): void {
            },
            []
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
            $commandTester->getDisplay()
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
            $commandTester->getDisplay()
        );
    }

    /**
     * @param mixed[] $data
     *
     * @return MockObject
     * @throws Exception
     */
    private function getMockedProvider(array $data): MockObject
    {
        /** @var MockObject|DocumentManager $dm */
        $dm = self::createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('flush')->willReturn(TRUE);

        /** @var MockObject|RedirectInterface $redirect */
        $redirect = self::createMock(RedirectInterface::class);
        $this->expectException(TypeError::class);

        /** @var MockObject|OAuth $oauth */
        $oauth = self::createPartialMock(OAuth::class, ['getRequestToken']);
        $oauth->method('getRequestToken')->willReturn($data);

        /** @var MockObject|OAuth1Provider $client */
        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$dm, $redirect])
            ->setMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
