<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Document;

use DateTime;
use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class ApplicationInstallTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Document
 */
final class ApplicationInstallTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::getExpires
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::getNonEncryptedSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setNonEncryptedSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::addNonEncryptedSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::setSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::getSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::addSettings
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::toArray
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall
     * @covers \Hanaboso\PipesPhpSdk\Application\Listener\ApplicationInstallListener
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

        $applicationInstall->setSettings(['secret' => 'settings']);
        $applicationInstall->addSettings(['token' => '123']);
        $this->pfd($applicationInstall);
        self::assertEquals(['secret' => 'settings', 'token' => '123'], $applicationInstall->getSettings());

        $this->dm->clear();

        /** @var ApplicationInstall $applicationInstall */
        $applicationInstall = $this->dm->getRepository(ApplicationInstall::class)->find($applicationInstall->getId());
        self::assertEquals(['secret' => 'settings', 'token' => '123'], $applicationInstall->getSettings());
    }

}
