<?php declare(strict_types=1);

namespace Tests\Integration\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use Hanaboso\Utils\Exception\DateTimeException;
use OAuth;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\DatabaseTestCaseAbstract;
use TypeError;

/**
 * Class AuthorizeUserCommandTest
 *
 * @package Tests\Integration\Command
 */
final class AuthorizeUserCommandTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testExecuteOauth2(): void
    {
        $kernel        = $this->createKernel(['environment' => 'oauthconsole']);
        $application   = new Application($kernel);
        $command       = $application->find('user:authorize');
        $commandTester = new CommandTester($command);

        $this->dm = $kernel->getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();

        $app = (new ApplicationInstall())
            ->setKey('null2')
            ->setUser('user');
        $this->pfd($app);
        $this->dm->clear();

        $commandTester->setInputs(['null2', 'user']);
        ob_start();
        $commandTester->execute(['command' => $command->getName(), '--env' => 'oauthconsole']);
        $content = ob_get_clean();

        $this->assertStringContainsString(
            'auth/ouath2/url.com?response_type=code&approval_prompt=auto&redirect_uri=127.0.0.4/api/applications/authorize/token&client_id=&state=dXNlcjpudWxsMg,,&access_type=offline',
            (string) $content
        );
    }

    /**
     * @throws DateTimeException
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
