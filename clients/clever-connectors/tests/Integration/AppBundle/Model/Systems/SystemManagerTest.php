<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\NullSystem;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SystemManagerTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems
 */
class SystemManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var SystemManager
     */
    private $manager;

    /**
     * @var SystemInstallRepository|DocumentRepository
     */
    private $repository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->manager    = $this->container->get('systems.manager');
        $this->repository = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     *
     */
    public function testGetSystems(): void
    {
        $systems = $this->manager->getSystems('system');

        $this->assertNotEmpty($systems);
        $this->assertInstanceOf(NullSystem::class, $systems[0]);
    }

    /**
     *
     */
    public function testGetSystemsNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->manager->getSystems('unknown');
    }

    /**
     *
     */
    public function testGetUserSystems(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null')
            ->setToken('token');
        $this->persistAndFlush($system);

        $systems = $this->manager->getUserSystems('user');

        $this->assertEquals(1, count($systems));
        $this->assertInstanceOf(NullSystem::class, $systems[0]);
    }

    /**
     *
     */
    public function testGetUserSystemsNoSystem(): void
    {
        $systems = $this->manager->getUserSystems('user');
        $this->assertEmpty($systems);
    }

    /**
     *
     */
    public function testGetUserSystemsNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('unknown')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->manager->getUserSystems('user');
    }

    /**
     *
     */
    public function testInstallSystem(): void
    {
        $system = $this->manager->installSystem('user', 'null', 'token');

        $this->assertEquals('user', $system->getUser());
        $this->assertEquals('null', $system->getSystem());
        $this->assertEquals('token', $system->getToken());
    }

    /**
     *
     */
    public function testInstallSystemNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->manager->installSystem('user', 'unknown', 'token');
    }

    /**
     *
     */
    public function testUninstallSystem(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->manager->uninstallSystem('user', 'null');

        $this->assertEmpty($this->repository->findBy(['user' => 'user', 'system' => 'null']));
    }

    /**
     *
     */
    public function testUninstallSystemNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->manager->uninstallSystem('user', 'unknown');
    }

    /**
     *
     */
    public function testSwitchToken(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null')
            ->setToken('token');
        $this->persistAndFlush($system);

        $system = $this->manager->switchToken('user', 'null', 'anotherToken');

        $this->assertEquals('anotherToken', $system->getToken());
    }

    /**
     *
     */
    public function testSwitchTokenNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->manager->switchToken('user', 'unknown', 'anotherToken');
    }

    /**
     *
     */
    public function testGetSystemUsers(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null')
            ->setToken('token')
            ->setSynchronized(TRUE);
        $this->persistAndFlush($system);

        $system = (new SystemInstall())
            ->setUser('anotherUser')
            ->setSystem('null')
            ->setToken('token')
            ->setSynchronized(FALSE);
        $this->persistAndFlush($system);

        $users = $this->manager->getSystemUsers('null', TRUE);

        $this->assertEquals(1, count($users));
        $this->assertEquals('user', $users[0]);

        $users = $this->manager->getSystemUsers('null', FALSE);

        $this->assertEquals(1, count($users));
        $this->assertEquals('anotherUser', $users[0]);
    }

    /**
     *
     */
    public function testGetSystemUsersNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->manager->getSystemUsers('unknown', TRUE);
    }

}