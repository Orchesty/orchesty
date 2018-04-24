<?php declare(strict_types=1);

namespace Tests\Integration\User\Repository\Entity;

use Hanaboso\PipesFramework\User\Entity\User;
use Hanaboso\PipesFramework\User\Repository\Entity\UserRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class UserRepositoryTest
 *
 * @package Tests\Integration\User\Repository\Entity
 */
final class UserRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetArrayOfUsers(): void
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $user->setPassword('pwd')
                ->setEmail('user' . $i);
            $em->persist($user);
            $em->flush($user);
        }
        $em->clear();

        /** @var UserRepository $rep */
        $rep   = $em->getRepository(User::class);
        $users = $rep->getArrayOfUsers();

        self::assertGreaterThanOrEqual(2, count($users));
        self::assertArrayHasKey('email', $users[0]);
        self::assertArrayHasKey('created', $users[0]);
    }

    /**
     *
     */
    public function testGetUserCount(): void
    {
        $em   = $this->container->get('doctrine.orm.default_entity_manager');
        $user = new User();
        $user
            ->setEmail('eml')
            ->setPassword('pwd');
        $em->persist($user);
        $em->flush($user);

        /** @var UserRepository $rep */
        $rep = $em->getRepository(User::class);

        self::assertGreaterThanOrEqual(1, $rep->getUserCount());
    }

}