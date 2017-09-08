<?php declare(strict_types=1);

namespace Tests\Integration\Acl\Manager;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Dto\GroupDto;
use Hanaboso\PipesFramework\Acl\Enum\ActionEnum;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\Acl\Manager\AccessManager;
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
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkObjectPermission()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::selectRule()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkGroupLvl()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::getObjectById()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::checkParams()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::throwPermissionException()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRight()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRightForUser()
     * @covers Hanaboso\PipesFramework\Acl\Manager\AccessManager::hasRightForGroup()
     */
    public function testIsAllowed(): void
    {
        $user = new User();
        $user
            ->setEmail('test@test.com')
            ->setPassword('pwd');
        $this->persistAndFlush($user);

        $rule = $this->createRule($user);

        self::assertInstanceOf(Group::class,
            $this->container->get('hbpf.access.manager')
                ->isAllowed(ActionEnum::WRITE, ResourceEnum::GROUP, $user, $rule->getGroup()->getId()));
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
     *
     * @return Rule
     */
    private function createRule(?User $user = NULL): Rule
    {
        $group = new Group($user);
        $rule  = new Rule();

        $rule
            ->setGroup($group)
            ->setResource(ResourceEnum::GROUP)
            ->setActionMask(7)
            ->setPropertyMask(2);
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

}