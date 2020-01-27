<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Handler;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\HbPFAppStore\Handler\ApplicationHandler;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\Utils\Exception\DateTimeException;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;
use InvalidArgumentException;

/**
 * Class ApplicationHandlerTest
 *
 * @package HbPFAppStoreTests\Integration\Handler
 */
final class ApplicationHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var ApplicationHandler
     */
    private $handler;

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::getApplicationByKey
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::getApplication
     * @covers \Hanaboso\HbPFAppStore\Loader\ApplicationLoader::getApplication
     *
     * @throws Exception
     */
    public function testGetApplicationByKey(): void
    {
        $response = $this->handler->getApplicationByKey('null');

        self::assertEquals(
            [
                'name'               => 'null',
                'authorization_type' => 'basic',
                'application_type'   => 'webhook',
                'key'                => 'null',
                'description'        => 'This is null app.',
            ],
            $response
        );
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::getApplicationsByUser
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::getApplication
     * @covers \Hanaboso\HbPFAppStore\Loader\ApplicationLoader::getApplication
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::getInstalledApplications
     *
     * @throws DateTimeException
     */
    public function testGetApplicationsByUser(): void
    {
        $this->createApplicationInstall('user', 'null');
        $this->createApplicationInstall('user', 'webhook');
        $result = $this->handler->getApplicationsByUser('user');

        self::assertEquals(2, count($result['items']));
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::getApplicationByKeyAndUser
     *
     * @throws DateTimeException
     * @throws ApplicationInstallException
     */
    public function testGetApplicationByKeyAndUser(): void
    {
        $this->createApplicationInstall('user', 'webhook');

        $result = $this->handler->getApplicationByKeyAndUser('webhook', 'user');
        self::assertEquals('Webhook', $result['name']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::updateApplicationSettings
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::saveApplicationSettings
     *
     * @throws DateTimeException
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->createApplicationInstall('user', 'null', ['form' => ['settings1' => 'Old settings']]);
        $result = $this->handler->updateApplicationSettings('null', 'user', ['settings1' => 'New settings']);
        self::assertEquals('New settings', $result['applicationSettings'][0]['value']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::updateApplicationPassword
     * @throws DateTimeException
     * @throws Exception
     */
    public function testUpdateApplicationPassword(): void
    {
        $this->createApplicationInstall('user', 'null');

        $result = $this->handler->updateApplicationPassword('null', 'user', ['password' => '_newPasswd_']);
        self::assertEquals('_newPasswd_', $result['settings']['authorization_settings']['password']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::updateApplicationPassword
     *
     * @throws DateTimeException
     * @throws Exception
     */
    public function testUpdateApplicationPasswordErr(): void
    {
        $this->createApplicationInstall('user', 'null');

        self::expectException(InvalidArgumentException::class);
        $this->handler->updateApplicationPassword('null', 'user', ['username' => 'newUsername']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::authorizeApplication
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::authorizeApplication
     * @covers \Hanaboso\HbPFAppStore\Loader\ApplicationLoader::getApplication
     * @throws Exception
     */
    public function testAuthorizeApplication(): void
    {
        $this->createApplicationInstall('user', 'null2');
        $this->handler->authorizeApplication('null2', 'user', 'redirect/url');
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::saveAuthToken
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::saveAuthorizationToken
     *
     * @throws ApplicationInstallException
     * @throws DateTimeException
     * @throws MongoDBException
     */
    public function testSaveAuthToken(): void
    {
        $this->createApplicationInstall(
            'user',
            'null2',
            [ApplicationInterface::AUTHORIZATION_SETTINGS => [ApplicationInterface::REDIRECT_URL => 'redirect_url']]
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
     * @param string  $user
     * @param string  $key
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     * @throws DateTimeException
     * @throws Exception
     */
    private function createApplicationInstall(
        string $user = 'user',
        string $key = 'key',
        array $settings = []
    ): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser($user)
            ->setKey($key)
            ->setSettings($settings);
        $this->persistAndFlush($applicationInstall);

        return $applicationInstall;
    }

}
