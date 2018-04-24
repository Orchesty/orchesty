<?php declare(strict_types=1);

namespace Tests\Integration\Acl\Manager;

use Hanaboso\CommonsBundle\Exception\EnumException;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Dto\GroupDto;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\User\Document\User;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AccessManagerTest
 *
 * @package Tests\Integration\Acl\Manager
 */
class AccessManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::throwPermissionException()
     */
    public function testWrongObjectArray(): void
    {
        $user = $this->createUser('wrongOne');
        $this->createRule($user);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'group', $user, []);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     */
    public function testWrongObjectBool(): void
    {
        $user = $this->createUser('wrongOne');
        $this->createRule($user);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, TRUE);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     */
    public function testWrongResourceAction(): void
    {
        $user = $this->createUser('resource');
        $this->createRule($user);
        $this->expect(EnumException::INVALID_CHOICE, EnumException::class);
        $this->container->get('hbpf.access.manager')->isAllowed('writdde', 'group', $user, NULL);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     */
    public function testWrongResourceResource(): void
    {
        $user = $this->createUser('resource');
        $this->createRule($user);
        $this->expect(EnumException::INVALID_CHOICE, EnumException::class);
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'grosdup', $user, NULL);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     */
    public function testReadPermission(): void
    {
        $user = $this->createUser('readPer');
        $this->createRule($user, 3, 'group', 2);
        self::assertTrue($this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, NULL));
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     */
    public function testReadPermissionNotAllowed(): void
    {
        $user = $this->createUser('nullReadPer');
        $this->createRule($user, 3, 'group', 1);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, NULL);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     */
    public function testWritePermission(): void
    {
        $user = $this->createUser('writePer');
        $this->createRule($user, 2, 'group', 1);
        self::assertTrue($res = $this->container->get('hbpf.access.manager')->isAllowed('write', 'group', $user, NULL));
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     */
    public function testWritePermissionNotAllowed(): void
    {
        $user = $this->createUser('nullWritePer');
        $this->createRule($user, 1, 'group', 2);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'group', $user, NULL);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     */
    public function testDeletePermission(): void
    {
        $user = $this->createUser('deletePer');
        $this->createRule($user, 6, 'group', 2);
        self::assertTrue($this->container->get('hbpf.access.manager')->isAllowed('delete', 'group', $user, NULL));
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     */
    public function testDeletePermissionNotAllowed(): void
    {
        $user = $this->createUser('nullDeletePer');
        $this->createRule($user, 6, 'group', 1);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('delete', 'group', $user, NULL);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     */
    public function testObjNonOwnerRight(): void
    {
        $tser  = $this->createUser('objTwner');
        $group = new Group($tser);
        $this->persistAndFlush($group);
        $user = $this->createUser('objOwner');

        $this->createRule($user, 7, 'group', 1);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, $group);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::getObjectById()
     */
    public function testIdNonOwnerRight(): void
    {
        $tser  = $this->createUser('objTidner');
        $group = new Group($tser);
        $this->persistAndFlush($group);
        $user = $this->createUser('objOwner');

        $this->createRule($user, 7, 'group', 1);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, $group->getId());
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::getObjectById()
     */
    public function testObjOwnerRight(): void
    {
        $user  = $this->createUser('objOwner');
        $group = new Group($user);
        $this->persistAndFlush($group);
        $this->createRule($user, 7, 'group', 1);
        $res = $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, $group);
        self::assertInstanceOf(Group::class, $res);
        $res = $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, $group->getId());
        self::assertInstanceOf(Group::class, $res);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::getObjectById()
     */
    public function testObjWithoutOwnerAllowed(): void
    {
        $user = $this->createUser('noOwnerAllow');
        $this->createRule($user, 7, 'node', 1);
        $node = new Node();
        $this->persistAndFlush($node);
        $res = $this->container->get('hbpf.access.manager')->isAllowed('read', 'node', $user, $node);
        self::assertInstanceOf(Node::class, $res);
        $res = $this->container->get('hbpf.access.manager')->isAllowed('read', 'node', $user, $node->getId());
        self::assertInstanceOf(Node::class, $res);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     */
    public function testObjWithoutOwnerNotAllowed(): void
    {
        $user = $this->createUser('noOwnerAllow');
        $this->createRule($user, 2, 'node', 2);
        $node = new Node();
        $this->persistAndFlush($node);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('read', 'node', $user, $node->getId());
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::getObjectById()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkGroupLvl()
     */
    public function testGroupAllowed(): void
    {
        $user = $this->createUser('groupAllowed');
        $this->createRule($user, 1, 'group', 2);
        $group = new Group(NULL);
        $this->persistAndFlush($group);
        $res = $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, $group);
        self::assertInstanceOf(Group::class, $res);
        $res = $this->container->get('hbpf.access.manager')->isAllowed('read', 'group', $user, $group->getId());
        self::assertInstanceOf(Group::class, $res);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkGroupLvl()
     */
    public function testGroupNotAllowed(): void
    {
        $user = $this->createUser('groupAllowed');
        $this->createRule($user, 1, 'group', 2);
        $group = new Group($user);
        $this->persistAndFlush($group);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'group', $user, $group);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRightForGroup()
     */
    public function testGroupLvlAllowed(): void
    {
        $user = $this->createUser('groupAllowed');
        $this->createRule($user, 7, 'group', 2);
        $group = new Group($user);
        $this->persistAndFlush($group->setLevel(8));
        $res = $this->container->get('hbpf.access.manager')->isAllowed('write', 'group', $user, $group);
        self::assertInstanceOf(Group::class, $res);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRightForGroup()
     */
    public function testGroupLvlNotAllowed(): void
    {
        $user = $this->createUser('groupNotAllowed');
        $this->createRule($user, 7, 'group', 2);
        $group = new Group($user);
        $this->persistAndFlush($group->setLevel(0));
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'group', $user, $group);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRightForUser()
     */
    public function testUserLvlAllowed(): void
    {
        $user = $this->createUser('userAllo');
        $this->createRule($user, 7, 'user', 2);
        $tser = $this->createUserLvl('tserAllo', 55);
        $res  = $this->container->get('hbpf.access.manager')->isAllowed('write', 'user', $user, $tser);
        self::assertInstanceOf(User::class, $res);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRightForUser()
     */
    public function testUserLvlNotAllowed(): void
    {
        $user = $this->createUser('userNotAllo');
        $this->createRule($user, 7, 'user', 2);
        $tser = $this->createUserLvl('tserNotAllo', 0);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'user', $user, $tser);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::isAllowed()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     */
    public function testClassPermision(): void
    {
        $user = $this->createUser('class');
        $this->createRule($user, 7, 'node', 2);
        $this->expect();
        $this->container->get('hbpf.access.manager')->isAllowed('write', 'node', $user, Node::class);
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::addGroup()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::updateGroup()
     */
    public function testAddAndUpdateGroup(): void
    {
        $user = new User();
        $user
            ->setEmail('test@test.com')
            ->setPassword('pwd');
        $this->persistAndFlush($user);

        $this->createRule($user);

        $access = $this->container->get('hbpf.access.manager');
        $access->addGroup('newGroup');
        /** @var Group $group */
        $group = $this->dm->getRepository(Group::class)->findOneBy(['name' => 'newGroup']);
        self::assertInstanceOf(Group::class, $group);

        $data = new GroupDto($group);
        $data
            ->addUser($user)
            ->addRule([
                [
                    'resource'      => 'user',
                    'action_mask'   => [
                        'write'  => 1,
                        'read'   => 1,
                        'delete' => 1,
                    ],
                    'property_mask' => [
                        'owner' => 1,
                        'group' => 1,
                    ],
                ],
                [
                    'resource'      => 'group',
                    'action_mask'   => [
                        'write'  => 1,
                        'read'   => 1,
                        'delete' => 1,
                    ],
                    'property_mask' => [
                        'owner' => 1,
                        'group' => 1,
                    ],
                ],
            ]);
        $group = $access->updateGroup($data);

        self::assertInstanceOf(Group::class, $group);
        self::assertEquals(2, count($group->getRules()));
    }

    /**
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::removeGroup()
     */
    public function testRemoveGroup(): void
    {
        $rule = $this->createRule();
        $this->dm->clear($rule);

        $this->container->get('hbpf.access.manager')->removeGroup($rule->getGroup());
        self::assertEmpty($this->dm->getRepository(Rule::class)->findAll());
    }

    /**
     * @param User|null $user
     * @param int       $act
     * @param string    $res
     * @param int       $prop
     *
     * @return Rule
     */
    private function createRule(?User $user = NULL, int $act = 7, string $res = 'group', int $prop = 2): Rule
    {
        $group = new Group($user);
        $rule  = new Rule();

        $rule
            ->setGroup($group->setLevel(5))
            ->setResource($res)
            ->setActionMask($act)
            ->setPropertyMask($prop);
        $group
            ->addRule($rule)
            ->setName('group');
        if ($user) {
            $group->addUser($user);
        }

        $this->dm->persist($rule);
        $this->dm->persist($group);
        $this->dm->flush();

        return $rule;
    }

    /**
     * @param string $usr
     *
     * @return User
     */
    private function createUser(string $usr = 'test@test.com'): User
    {
        $user = new User();
        $user
            ->setEmail($usr)
            ->setPassword('pwd');
        $this->persistAndFlush($user);

        return $user;
    }

    /**
     * @param string $usr
     * @param int    $lvl
     *
     * @return User
     */
    private function createUserLvl(string $usr = 'test@test.com', int $lvl): User
    {
        $user = new User();
        $user
            ->setEmail($usr)
            ->setPassword('pwd');
        $this->persistAndFlush($user);

        $group = new Group($user);
        $group->setLevel($lvl)->addUser($user);

        $this->persistAndFlush($group);

        return $user;
    }

    /**
     * @param int    $code
     * @param string $class
     */
    private function expect(int $code = AclException::PERMISSION, string $class = AclException::class): void
    {
        $this->expectException($class);
        $this->expectExceptionCode($code);
    }

}