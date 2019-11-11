<?php declare(strict_types=1);

namespace Tests\Integration\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use OAuth;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;
use TypeError;

/**
 * Class AuthorizeUserCommandTest
 *
 * @package Tests\Integration\Command
 */
final class AuthorizeUserCommandTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Exception
     */
    public function testExecuteOauth2(): void
    {
        $app = (new ApplicationInstall())
            ->setKey('null2')
            ->setUser('user');
        $this->persistAndFlush($app);

        $kernel        = $this->createKernel(['environment' => 'oauthconsole']);
        $application   = new Application($kernel);
        $command       = $application->find('user:authorize');
        $commandTester = new CommandTester($command);

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
        $this->persistAndFlush($app);

        $install = new ApplicationInstall();
        /** @var OAuth1Provider|MockObject $provider */
        $provider = $this->getMockedProvider(
            ['oauth_token' => 'aabbcc', 'oauth_token_secret' => '112233'],
            'authorize/url?oauth_callback=127.0.0.4&oauth_token=aabbcc'
        );
        $dto      = new OAuth1Dto($install);

        $provider->authorize(
            $dto,
            'token/url',
            'authorize/url',
            function (): void {
            },
            []
        );

        $this->expectOutputString('authorize/url?oauth_callback=127.0.0.4&oauth_token=aabbcc');
    }

    /**
     * @param array  $data
     * @param string $authorizeUrl
     *
     * @return MockObject
     * @throws Exception
     */
    private function getMockedProvider(array $data, string $authorizeUrl): MockObject
    {
        $dm = self::createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('flush')->willReturn(TRUE);

        $redirect = self::createMock(RedirectInterface::class);
        $this->expectException(TypeError::class);
        $redirect->method('make')->will(print_r($authorizeUrl));

        $oauth = self::createPartialMock(
            OAuth::class,
            ['getRequestToken']
        );
        $oauth->method('getRequestToken')->willReturn($data);
        sprintf(
            '#Parameter #1 $stub of method                                           
         PHPUnit\Framework\MockObject\Builder\InvocationMocker::will() expects  
         PHPUnit\Framework\MockObject\Stub\Stub, true given#'
        );

        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$dm, $redirect])
            ->setMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
