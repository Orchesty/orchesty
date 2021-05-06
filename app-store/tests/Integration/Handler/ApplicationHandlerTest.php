<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Handler;

use Exception;
use Hanaboso\HbPFAppStore\Handler\ApplicationHandler;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;
use InvalidArgumentException;

/**
 * Class ApplicationHandlerTest
 *
 * @package HbPFAppStoreTests\Integration\Handler
 *
 * @covers  \Hanaboso\HbPFAppStore\Handler\ApplicationHandler
 */
final class ApplicationHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ApplicationHandler
     */
    private ApplicationHandler $handler;

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::getApplicationsByUser
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::getApplication
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::getInstalledApplications
     *
     * @throws Exception
     */
    public function testGetApplicationsByUser(): void
    {
        $this->createApplicationInstall('null');
        $this->createApplicationInstall('webhook');
        $result = $this->handler->getApplicationsByUser('user');

        self::assertEquals(2, count($result['items']));
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::getApplicationByKeyAndUser
     *
     * @throws Exception
     */
    public function testGetApplicationByKeyAndUser(): void
    {
        $this->createApplicationInstall('webhook');

        $result = $this->handler->getApplicationByKeyAndUser('webhook', 'user');
        self::assertEquals('Webhook', $result['name']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::updateApplicationSettings
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::saveApplicationSettings
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->createApplicationInstall('null', ['form' => ['settings1' => 'Old settings']]);
        $result = $this->handler->updateApplicationSettings('null', 'user', ['settings1' => 'New settings']);
        self::assertEquals('New settings', $result['applicationSettings'][0]['value']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws Exception
     */
    public function testUpdateApplicationPassword(): void
    {
        $this->createApplicationInstall('null');

        $result = $this->handler->updateApplicationPassword('null', 'user', ['password' => '_newPasswd_']);
        self::assertEquals('_newPasswd_', $result['settings']['authorization_settings']['password']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws Exception
     */
    public function testUpdateApplicationPasswordErr(): void
    {
        $this->createApplicationInstall('null');

        self::expectException(InvalidArgumentException::class);
        $this->handler->updateApplicationPassword('null', 'user', ['username' => 'newUsername']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::authorizeApplication
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::authorizeApplication
     *
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $this->createApplicationInstall('null2');
        $this->handler->authorizeApplication('null2', 'user', 'redirect/url');
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::saveAuthToken
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::saveAuthorizationToken
     *
     * @throws Exception
     */
    public function testSaveAuthToken(): void
    {
        $this->createApplicationInstall(
            'null2',
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::REDIRECT_URL => 'redirect_url']],
        );
        $result = $this->handler->saveAuthToken('null2', 'user', ['token']);

        self::assertEquals('redirect_url', $result['redirect_url']);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::$container->get('hbpf._application.handler.application');
    }

    /**
     * @param string  $key
     * @param mixed[] $settings
     *
     * @throws Exception
     */
    private function createApplicationInstall(string $key = 'key', array $settings = []): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser('user')
            ->setKey($key)
            ->setSettings($settings);
        $this->persistAndFlush($applicationInstall);
    }

}
