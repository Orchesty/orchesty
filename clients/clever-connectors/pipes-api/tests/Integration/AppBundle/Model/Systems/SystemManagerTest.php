<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\TopologyGenerator\Request\RequestHandler;
use Tests\DatabaseTestCaseAbstract;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;
use Tests\PrivateTrait;

/**
 * Class SystemManagerTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems
 */
final class SystemManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

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
        $this->manager    = $this->container->get('cc.systems.manager');
        $this->repository = $this->dm->getRepository(SystemInstall::class);
    }

    /**
     *
     */
    public function testGetSystemsBySystems(): void
    {
        $this->assertEquals(21, count($this->manager->getSystems()));
    }

    /**
     *
     */
    public function testGetSystemsByUserAndGroup(): void
    {
        $this->assertEquals(1, count($this->manager->getSystems('someUser', 'someGroup')));
    }

    /**
     *
     */
    public function testGetSystemsByUser(): void
    {
        $this->assertEquals(2, count($this->manager->getSystems('someUser')));
    }

    /**
     *
     */
    public function testGetSystemsByUserNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->manager->getSystems('unknown');
    }

    /**
     *
     */
    public function testGetSystemsByGroup(): void
    {
        $this->assertEquals(2, count($this->manager->getSystems(NULL, 'someGroup')));
    }

    /**
     *
     */
    public function testGetSystemsByGroupNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->manager->getSystems(NULL, 'unknown');
    }

    /**
     *
     */
    public function testGetUserSystems(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
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
        $systems = $this->manager->getUserSystems('unknown');
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
        $system = $this->manager->installSystem('user', 'null.user.group', 'token');

        $this->assertEquals('user', $system->getUser());
        $this->assertEquals('null.user.group', $system->getSystem());
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
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->manager->uninstallSystem('user', 'null.user.group');

        $this->assertEmpty($this->repository->findBy(['user' => 'user', 'system' => 'null.user.group']));
    }

    /**
     *
     */
    public function testUninstallSystemNotFoundUser(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_OR_USER_NOT_FOUND);

        $this->manager->uninstallSystem('unknown', 'null.user.group');
    }

    /**
     *
     */
    public function testUninstallSystemNotFoundSystem(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_OR_USER_NOT_FOUND);

        $this->manager->uninstallSystem('user', 'unknown');
    }

    /**
     *
     */
    public function testSwitchToken(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $system = $this->manager->switchToken('user', 'null.user.group', 'anotherToken');

        $this->assertEquals('anotherToken', $system->getToken());
    }

    /**
     *
     */
    public function testSwitchTokenNotFoundUser(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_OR_USER_NOT_FOUND);

        $this->manager->switchToken('unknown', 'null.user.group', 'anotherToken');
    }

    /**
     *
     */
    public function testSwitchTokenNotFoundSystem(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_OR_USER_NOT_FOUND);

        $this->manager->switchToken('user', 'unknown', 'anotherToken');
    }

    /**
     *
     */
    public function testGetSystemUsers(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSynchronized(TRUE);
        $this->persistAndFlush($system);

        $system = (new SystemInstall())
            ->setUser('anotherUser')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSynchronized(FALSE);
        $this->persistAndFlush($system);

        $users = $this->manager->getSystemUsers('null.user.group', TRUE);

        $this->assertEquals(1, count($users));
        $this->assertEquals('user', $users[0]);

        $users = $this->manager->getSystemUsers('null.user.group', FALSE);

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

    /**
     *
     */
    public function testSetPassword(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSettings(['password' => 'pass1']);
        $this->persistAndFlush($system);

        $systemInstall = $this->manager->setPassword('user', 'null.user.group', 'pass2');

        $this->assertEquals('pass2', $systemInstall->getSettings()['password']);
    }

    /**
     *
     */
    public function testGetSystemInstall(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSettings(['password' => 'pass1']);
        $this->persistAndFlush($system);

        $systemInstall = $this->manager->setPassword('user', 'null.user.group', 'pass2');

        $this->assertEquals($system, $systemInstall);
    }

    /**
     *
     */
    public function testGetSystemInstallFail(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSettings(['password' => 'pass1']);
        $this->persistAndFlush($system);

        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_OR_USER_NOT_FOUND);

        $this->manager->setPassword('unknown', 'null.user.group', 'pass2');
    }

    /**
     *
     */
    public function testDeleteTopology(): void
    {
        $manager = $this->container->get('cc.systems.manager');

        $top = new Topology();
        $top->setName('ttop');
        $this->dm->persist($top);
        $this->dm->flush($top);

        $nodes = [];
        for ($i = 0; $i < 2; $i++) {
            $nodes[$i] = new Node();
            $nodes[$i]->setTopology($top->getId());
            $this->dm->persist($nodes[$i]);
        }
        $this->dm->flush();

        $responseDto    = $this->getMockBuilder(ResponseDto::class)->disableOriginalConstructor()->getMock();
        $requestHandler = $this
            ->getMockBuilder(RequestHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestHandler
            ->expects($this->once())
            ->method('deleteTopology')
            ->with($top->getId())
            ->willReturn($responseDto);
        $this->setProperty($manager, 'requestHandler', $requestHandler);

        $manager->deleteTopology($top, [], $nodes, []);
        $this->dm->clear();
        self::assertNull($this->dm->getRepository(Topology::class)->findOneBy(['name' => 'ttop']));
        self::assertEmpty($this->dm->getRepository(Node::class)->findBy(['topology' => $top->getId()]));
    }

}