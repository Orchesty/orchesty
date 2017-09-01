<?php declare(strict_types=1);

namespace Tests\Integration\Acl\Reposity\Document;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Repository\Document\GroupRepository;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Repository\Document\UserRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class GroupRepositoryTest
 *
 * @package Tests\Integration\Acl\Reposity\Document
 */
class GroupRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers GroupRepository::getUserGroups()
     */
    public function testUserGroups(): void
    {
        $user  = new User();
        $user2 = new User();
        $this->persistAndFlush($user);
        $this->persistAndFlush($user2);

        $group  = (new Group($user))->addUser($user);
        $group2 = (new Group($user))->addUser($user2);
        $this->persistAndFlush($group);
        $this->persistAndFlush($group2);

        $this->dm->clear();
        /** @var UserRepository $rep */
        $rep = $this->dm->getRepository(User::class);
        /** @var User $user */
        $user = $rep->find($user->getId());
        /** @var GroupRepository $rep */
        $rep = $this->dm->getRepository(Group::class);
        $res = $rep->getUserGroups($user);
        self::assertEquals(1, count($res));
    }

}