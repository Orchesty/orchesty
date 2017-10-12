<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Database;

use CleverConnectors\AppBundle\Enum\DatabaseFilterEnum;
use Hanaboso\PipesFramework\User\Document\User;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DeletedFilterTest
 *
 * @package Tests\Integration\AppBundle\Database
 */
final class DeletedFilterTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testDeletedFilter(): void
    {
        $repository = $this->dm->getRepository(User::class);

        $this->persistAndFlush((new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd')
            ->setDeleted(TRUE)
        );

        $this->dm->clear();
        $this->assertEquals(0, count($repository->findBy(['email' => 'email@example.com'])));

        $this->dm->getFilterCollection()->disable(DatabaseFilterEnum::DELETED);
        $this->dm->clear();
        $this->assertEquals(1, count($repository->findBy(['email' => 'email@example.com'])));

        $this->dm->getFilterCollection()->enable(DatabaseFilterEnum::DELETED);
        $this->dm->clear();
        $this->assertEquals(0, count($repository->findBy(['email' => 'email@example.com'])));

    }

}