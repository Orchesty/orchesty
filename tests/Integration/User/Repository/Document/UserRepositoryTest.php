<?php declare(strict_types=1);

namespace Tests\Integration\User\Repository\Document;

use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Repository\Document\UserRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class UserRepositoryTest
 *
 * @package Tests\Integration\User\Repository\Document
 */
final class UserRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetArrayOfUsers(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $user = new User();
            $user->setPassword('pwd')
                ->setEmail('user' . $i);
            $this->persistAndFlush($user);
        }
        $this->dm->clear();

        /** @var UserRepository $rep */
        $rep   = $this->dm->getRepository(User::class);
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
        $user = new User();
        $user
            ->setEmail('eml')
            ->setPassword('pwd');
        $this->persistAndFlush($user);
        $user = new User();
        $user
            ->setEmail('eml2')
            ->setPassword('pwd');
        $this->persistAndFlush($user);

        /** @var UserRepository $rep */
        $rep = $this->dm->getRepository(User::class);

        self::assertGreaterThanOrEqual(1, $rep->getUserCount());
    }

}