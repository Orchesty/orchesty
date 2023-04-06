<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Application\Document;

use DateTime;
use Exception;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationInstallTest
 *
 * @package PipesFrameworkTests\Integration\Application\Document
 */
final class ApplicationInstallTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::getExpires
     * @covers \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::getNonEncryptedSettings
     * @covers \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::setNonEncryptedSettings
     * @covers \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::addNonEncryptedSettings
     * @covers \Hanaboso\PipesFramework\Application\Document\ApplicationInstall::toArray
     * @covers \Hanaboso\PipesFramework\Application\Document\ApplicationInstall
     *
     * @throws Exception
     */
    public function testApplicationInstall(): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser('user')
            ->setKey('null-key')
            ->setExpires(DateTimeUtils::getUtcDateTime('now'))
            ->setNonEncryptedSettings(['lock' => TRUE])
            ->addNonEncryptedSettings(['unlock' => FALSE]);
        $this->pfd($applicationInstall);
        $this->dm->clear();

        /** @var ApplicationInstall $applicationInstall */
        $applicationInstall = $this->dm->getRepository(ApplicationInstall::class)->find($applicationInstall->getId());

        self::assertInstanceOf(DateTime::class, $applicationInstall->getExpires());
        self::assertEquals(['lock' => TRUE, 'unlock' => FALSE], $applicationInstall->getNonEncryptedSettings());
        self::assertEquals(8, count($applicationInstall->toArray()));
    }

}
