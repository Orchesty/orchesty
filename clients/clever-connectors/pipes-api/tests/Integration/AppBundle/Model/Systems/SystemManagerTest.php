<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\DataLayout;
use CleverConnectors\AppBundle\Document\MapTemplate;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemUITypeEnum;
use CleverConnectors\AppBundle\Enum\TypeEnum;
use CleverConnectors\AppBundle\Exceptions\Exception;
use CleverConnectors\AppBundle\Model\Systems\Dto\ActionDto;
use CleverConnectors\AppBundle\Model\Systems\Dto\SystemData;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\SystemManager;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\RequestHandler;
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
     * @throws Exception
     */
    public function testGetSystemsByUserAndGroup(): void
    {
        $this->assertEquals(1, count($this->manager->getSystems('someUser', 'someGroup')));
    }

    /**
     * @throws Exception
     */
    public function testGetSystemsByUser(): void
    {
        $this->assertEquals(2, count($this->manager->getSystems('someUser')));
    }

    /**
     * @throws Exception
     */
    public function testGetSystemsByUserNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->manager->getSystems('unknown');
    }

    /**
     * @throws Exception
     */
    public function testGetSystemsByGroup(): void
    {
        $this->assertEquals(2, count($this->manager->getSystems(NULL, 'someGroup')));
    }

    /**
     * @throws Exception
     */
    public function testGetSystemsByGroupNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_PROPERTY_NOT_FOUND);

        $this->manager->getSystems(NULL, 'unknown');
    }

    /**
     * @throws Exception
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
     * @throws Exception
     */
    public function testGetUserSystemsNoSystem(): void
    {
        $systems = $this->manager->getUserSystems('unknown');
        $this->assertEmpty($systems);
    }

    /**
     * @throws Exception
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
     * @throws Exception
     */
    public function testGetUserSystem(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $action = TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $system->getSystem());

        $dataLayoutManager = $this->container->get('cc.layout.manager');
        $datalayout        = $dataLayoutManager->createDataLayout($system, [
            'action' => $action,
            'fields' => [
                ['key' => 'key-text', 'type' => TypeEnum::TEXT],
                ['key' => 'key-date', 'type' => TypeEnum::DATE],
                ['key' => 'key-bool', 'type' => TypeEnum::BOOL],
            ],
        ]);

        $mapTemplateManager = $this->container->get('cc.map_template.manager');
        $map                = $mapTemplateManager->create($system, [
            'action'    => $action,
            'direction' => MapTemplate::DIRECTION_IN,
            'fields'    => [
                [
                    'key'   => 'abc',
                    'name'  => 'abc',
                    'type'  => TypeEnum::TEXT,
                    'items' => ['Item One', 'Item Two'],
                ],
            ],
        ]);

        $this->assertEquals([
            'key'              => 'null.user.group',
            'name'             => 'NULL',
            'description'      => 'Only for testing purposes',
            'type'             => 'cron',
            'ui_type'          => SystemUITypeEnum::BASIC,
            'auth_type'        => 'oauth2',
            'authorized'       => FALSE,
            'token'            => 'token',
            'synchronized'     => FALSE,
            'eventCreate'      => FALSE,
            'eventUnsubscribe' => FALSE,
            'eventHardBounce'  => FALSE,
            'eventSubscribe'   => FALSE,
            'setting_fields'   => [
                0        => [
                    'type'        => 'url',
                    'key'         => 'field1',
                    'label'       => '',
                    'value'       => NULL,
                    'required'    => TRUE,
                    'read_only'   => FALSE,
                    'disabled'    => FALSE,
                    'description' => '',
                    'choices'     =>
                        [
                        ],
                    'action'      => '',
                    'depends_on'  => '',
                ], 1     =>
                    [
                        'type'        => 'text',
                        'key'         => 'field2',
                        'label'       => '',
                        'value'       => NULL,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                        'choices'     =>
                            [
                            ],
                        'action'      => '',
                        'depends_on'  => '',
                    ], 2 =>
                    [
                        'type'        => 'password',
                        'key'         => 'field3',
                        'label'       => '',
                        'value'       => FALSE,
                        'required'    => TRUE,
                        'read_only'   => FALSE,
                        'disabled'    => FALSE,
                        'description' => '',
                        'choices'     =>
                            [
                            ],
                        'action'      => '',
                        'depends_on'  => '',
                    ],
            ],
            'data_layouts'     => [
                0 => [
                    '_id'    => $datalayout->getId(),
                    'action' => $action,
                    'fields' => [
                        0        =>
                            [
                                'key'  => 'key-text',
                                'type' => 'text',
                            ], 1 =>
                            [
                                'key'  => 'key-date',
                                'type' => 'date',
                            ], 2 =>
                            [
                                'key'  => 'key-bool',
                                'type' => 'bool',
                            ],
                    ],
                ],
            ],
            'map_templates'    => [
                0 => [
                    '_id'       => $map->getId(),
                    'action'    => $action,
                    'direction' => 'in',
                    'fields'    => [
                        0 => [
                            'key'   => 'abc',
                            'name'  => 'abc',
                            'type'  => 'text',
                            'items' => [
                                0 => 'Item One',
                                1 => 'Item Two',
                            ],
                        ],
                    ],
                ],
            ],
            'actions'          => [
                $action,
            ],
        ], $this->manager->getUserSystem($system));
    }

    /**
     * @throws Exception
     */
    public function testInstallSystem(): void
    {
        $system = $this->manager->installSystem('user', 'null.user.group', 'token');

        $this->assertEquals('user', $system->getUser());
        $this->assertEquals('null.user.group', $system->getSystem());
        $this->assertEquals('token', $system->getToken());
    }

    /**
     * @throws Exception
     */
    public function testInstallSystemNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->manager->installSystem('user', 'unknown', 'token');
    }

    /**
     * @throws Exception
     */
    public function testUninstallSystem(): void
    {
        $system = (new SystemInstall())
            ->setUser('user')
            ->setSystem('null.user.group')
            ->setToken('token');
        $this->persistAndFlush($system);

        $dto = new ActionDto(
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $system->getSystem()),
            MapTemplate::DIRECTION_IN
        );

        $map = (new MapTemplate())
            ->setAction($dto)
            ->setDirection($dto)
            ->setSystemInstall($system);
        $this->persistAndFlush($map);

        $layout = (new DataLayout())
            ->setAction($dto)
            ->setSystemInstall($system);
        $this->persistAndFlush($layout);

        $this->dm->clear();

        $this->assertNotEmpty($this->repository->findBy(['user' => 'user', 'system' => 'null.user.group']));
        $this->assertNotEmpty($this->dm->getRepository(MapTemplate::class)
            ->findBy(['systemInstall' => $system->getId()]));
        $this->assertNotEmpty($this->dm->getRepository(DataLayout::class)
            ->findBy(['systemInstall' => $system->getId()]));

        $this->manager->uninstallSystem('user', 'null.user.group');

        $this->dm->clear();

        $this->assertEmpty($this->repository->findBy(['user' => 'user', 'system' => 'null.user.group']));
        $this->assertEmpty($this->dm->getRepository(MapTemplate::class)->findBy(['systemInstall' => $system->getId()]));
        $this->assertEmpty($this->dm->getRepository(DataLayout::class)->findBy(['systemInstall' => $system->getId()]));
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function testGetSystemUsers(): void
    {

        $this->persistAndFlush((new SystemInstall())
            ->setUser('user-one')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSynchronized(TRUE));

        $this->persistAndFlush((new SystemInstall())
            ->setUser('user-two')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSynchronized(TRUE));

        $this->persistAndFlush((new SystemInstall())
            ->setUser('anotherUser')
            ->setSystem('null.user.group')
            ->setToken('token')
            ->setSynchronized(FALSE));

        $users = $this->manager->getSystemUsers('null.user.group', 1, 50, TRUE);

        $this->assertEquals(2, count($users));
        $this->assertEquals('user-one', $users[0]);
        $this->assertEquals('user-two', $users[1]);

        $users = $this->manager->getSystemUsers('null.user.group', 1, 50, FALSE);

        $this->assertEquals(1, count($users));
        $this->assertEquals('anotherUser', $users[0]);
    }

    /**
     * @throws Exception
     */
    public function testGetSystemUsersNotFound(): void
    {
        $this->expectException(SystemException::class);
        $this->expectExceptionCode(SystemException::SYSTEM_NOT_FOUND);

        $this->manager->getSystemUsers('unknown', 1, 50, TRUE);
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
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

    /**
     * @throws Exception
     */
    public function testRunCustomAction(): void
    {
        $sysInstall = new SystemInstall();
        $sysInstall
            ->setSystem('null.user.group')
            ->setUser('123');
        $this->persistAndFlush($sysInstall);

        $manager = $this->container->get('cc.systems.manager');
        $result  = $manager->runCustomAction('null.user.group', '123', 'customAction', []);

        self::assertTrue(is_array($result));
        self::assertNotEmpty($result);
        self::assertArrayHasKey('processed', $result);
        self::assertArrayHasKey('user', $result);
        self::assertTrue($result['processed']);
        self::assertEquals($sysInstall->getUser(), $result['user']);
    }

    /**
     * @throws Exception
     */
    public function testRunCustomActionEx(): void
    {
        $sysInstall = new SystemInstall();
        $sysInstall
            ->setSystem('null.user.group')
            ->setUser('123');
        $this->persistAndFlush($sysInstall);

        $manager = $this->container->get('cc.systems.manager');
        $this->expectException(SystemException::class);
        $manager->runCustomAction('null.user.group', '123', 'nonExistAction', []);
    }

    /**
     * @throws Exception
     */
    public function testSystemCount(): void
    {
        $manager = $this->container->get('cc.systems.manager');
        self::assertGreaterThanOrEqual(25, $manager->getSystemCount());
    }

    /**
     * @throws Exception
     * @throws EnumException
     */
    public function testGetSystemLists(): void
    {
        $manager = $this->container->get('cc.systems.manager');
        $res     = $manager->getSystemList();

        self::assertGreaterThanOrEqual(25, count($res));
        self::assertInstanceOf(SystemData::class, $res[11]);
    }

}