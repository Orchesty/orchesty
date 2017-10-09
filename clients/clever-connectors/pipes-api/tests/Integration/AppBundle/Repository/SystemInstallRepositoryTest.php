<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 14:56
 */

namespace Tests\Integration\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use LogicException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SystemInstallRepositoryTest
 *
 * @package Tests\Integration\AppBundle\Repository
 */
final class SystemInstallRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetSystemInstall(): void
    {
        $system = new SystemInstall();
        $system
            ->setUser('u-123')
            ->setToken('t-456')
            ->setSystem('s-789');

        $this->dm->persist($system);
        $this->dm->flush($system);
        $this->dm->clear();

        /** @var SystemInstallRepository $repo */
        $repo = $this->dm->getRepository(SystemInstall::class);
        $sys  = $repo->getSystemInstall($system->getUser(), $system->getToken(), $system->getSystem());
        $this->assertInstanceOf(SystemInstall::class, $sys);
    }

    /**
     *
     */
    public function testGetSystemInstallEx(): void
    {
        $system = new SystemInstall();
        $system
            ->setUser('u-123' . uniqid())
            ->setToken('t-456')
            ->setSystem('s-789');

        /** @var SystemInstallRepository $repo */
        $repo = $this->dm->getRepository(SystemInstall::class);
        $this->expectException(LogicException::class);
        $repo->getSystemInstall($system->getUser(), $system->getToken(), $system->getSystem());
    }

}