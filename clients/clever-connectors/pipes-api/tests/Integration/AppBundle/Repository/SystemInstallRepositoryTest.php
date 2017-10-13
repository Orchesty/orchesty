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
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
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
    public function testGetSystemInstallFromHeaders(): void
    {
        $system = new SystemInstall();
        $system
            ->setUser('u-123')
            ->setToken('t-456')
            ->setSystem('s-789');

        $this->dm->persist($system);
        $this->dm->flush($system);
        $this->dm->clear();

        $arr = [
            CMHeaders::createKey(CMHeaders::GUID)       => $system->getUser(),
            CMHeaders::createKey(CMHeaders::TOKEN)      => $system->getToken(),
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => $system->getSystem(),
        ];

        /** @var SystemInstallRepository $repo */
        $repo = $this->dm->getRepository(SystemInstall::class);
        $sys  = $repo->getSystemInstallFromHeaders($arr);
        $this->assertInstanceOf(SystemInstall::class, $sys);
    }

    /**
     *
     */
    public function testGetSystemInstallFromHeadersEx(): void
    {
        $system = new SystemInstall();
        $system
            ->setUser('u-123')
            ->setToken('t-456')
            ->setSystem('s-789');

        $this->dm->persist($system);
        $this->dm->flush($system);
        $this->dm->clear();

        $arr = [
            CMHeaders::createKey(CMHeaders::GUID)       => $system->getUser(),
            CMHeaders::createKey(CMHeaders::SYSTEM_KEY) => $system->getSystem(),
        ];

        /** @var SystemInstallRepository $repo */
        $repo = $this->dm->getRepository(SystemInstall::class);
        $this->expectException(LogicException::class);
        $repo->getSystemInstallFromHeaders($arr);
    }

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

    /**
     *
     */
    public function testSetSyncTime(): void
    {
        $system = new SystemInstall();
        $system
            ->setUser('u-123' . uniqid())
            ->setToken('t-456')
            ->setSystem('s-789');
        $this->dm->persist($system);
        $this->dm->flush($system);
        $this->dm->clear();

        /** @var SystemInstallRepository $repo */
        $repo = $this->dm->getRepository(SystemInstall::class);
        $repo->setSyncTime($system);

        $this->dm->clear();
        /** @var SystemInstall $sys */
        $sys = $repo->find($system->getId());
        $this->assertNotEmpty($sys->getSynchronizedTime());
        $this->assertTrue($sys->isSynchronized());
    }

    /**
     *
     */
    public function testFindBeforeExpiration(): void
    {
        /** @var SystemInstallRepository $repo */
        $repo = $this->dm->getRepository(SystemInstall::class);

        $datetime = new DateTime();
        $datetime->setTimestamp(time() + 7200);

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('user')
            ->setSystem('system')
            ->setExpires($datetime);

        $this->persistAndFlush($systemInstall);

        $systemInstall2 = new SystemInstall();
        $systemInstall2
            ->setUser('user2')
            ->setSystem('system2')
            ->setExpires(NULL);

        $this->persistAndFlush($systemInstall2);

        $datetime->setTimestamp(time() + 3600);
        $results = $repo->findBeforeExpiration($datetime);

        self::assertCount(0, $results);

        $datetime->setTimestamp(time() + 7200);
        $results = $repo->findBeforeExpiration($datetime);

        self::assertCount(1, $results);
    }

}