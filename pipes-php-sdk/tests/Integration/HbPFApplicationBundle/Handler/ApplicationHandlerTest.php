<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use InvalidArgumentException;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
final class ApplicationHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ApplicationHandler
     */
    private $handler;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getApplications
     */
    public function testGetApplication(): void
    {
        self::assertEquals(['null', 'null2', 'null3'], $this->handler->getApplications());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::updateApplicationSettings
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $this->handler->updateApplicationSettings(
            'null',
            'user',
            [BasicApplicationInterface::USER => 'thisIsMe']
        );

        self::assertEquals('thisIsMe', $applicationInstall->getSettings()[ApplicationAbstract::FORM]['user']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws Exception
     */
    public function testUpdateApplicationPassword(): void
    {
        $applicationInstall = $this->createApplicationInstall();
        $this->handler->updateApplicationPassword('null', 'user', ['password' => '__very_secret__']);

        self::assertEquals(
            '__very_secret__',
            $applicationInstall->getSettings(
            )[ApplicationInterface::AUTHORIZATION_SETTINGS][BasicApplicationInterface::PASSWORD]
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws Exception
     */
    public function testUpdateApplicationPasswordErr(): void
    {
        $this->createApplicationInstall();
        self::expectException(InvalidArgumentException::class);
        $this->handler->updateApplicationPassword('null', 'user', ['passwd' => '__very_secret__']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::authorizeApplication
     *
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $this->createApplicationInstall();
        $manager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $manager->expects(self::any())->method('authorizeApplication');

        $handler = new ApplicationHandler($manager);
        $handler->authorizeApplication('null', 'user', '/redirect/url');
        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::saveAuthToken
     *
     * @throws Exception
     */
    public function testSaveAuthToken(): void
    {
        $this->createApplicationInstall();
        $manager = self::createPartialMock(ApplicationManager::class, ['authorizeApplication']);
        $manager->expects(self::any())->method('authorizeApplication');

        $handler = new ApplicationHandler($manager);
        $handler->authorizeApplication('null', 'user', '/redirect/url');
        self::assertTrue(TRUE);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::saveAuthToken
     *
     * @throws Exception
     */
    public function testAuthToken(): void
    {
        $manager = self::createPartialMock(ApplicationManager::class, ['saveAuthorizationToken']);
        $manager
            ->expects(self::any())->method('saveAuthorizationToken')
            ->willReturn([ApplicationInterface::REDIRECT_URL => '/redirect/url']);

        $handler     = new ApplicationHandler($manager);
        $redirectUrl = $handler->saveAuthToken('null', 'user', ['code' => '__code__']);

        self::assertEquals(['redirect_url' => '/redirect/url'], $redirectUrl);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::$container->get('hbpf.application.handler');
    }

    /**
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(array $settings = []): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setKey('null')
            ->setUser('user')
            ->setSettings($settings);

        $this->pfd($applicationInstall);

        return $applicationInstall;
    }

}
