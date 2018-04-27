<?php declare(strict_types=1);

namespace Tests\Integration\Acl\Document;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\UserBundle\Document\User;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DocumentTest
 *
 * @package Tests\Integration\Acl\Document
 */
class DocumentTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testReferences(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $group = (new Group($user))
            ->setName('Group')
            ->addUser($user);
        $this->persistAndFlush($group);

        $rule = (new Rule())
            ->setResource('R1')
            ->setGroup($group);
        $this->persistAndFlush($rule);

        $group->addRule($rule);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Group $existingGroup */
        $existingGroup = $this->dm->getRepository(Group::class)->find($group->getId());

        $this->assertSame($group->getName(), $existingGroup->getName());
        $this->assertSame(1, count($group->getUsers()));
        $this->assertSame($group->getUsers()[0]->getEmail(), $existingGroup->getUsers()[0]->getEmail());
        $this->assertSame(1, count($group->getRules()));
        $this->assertSame($group->getRules()[0]->getResource(), $existingGroup->getRules()[0]->getResource());
    }

}