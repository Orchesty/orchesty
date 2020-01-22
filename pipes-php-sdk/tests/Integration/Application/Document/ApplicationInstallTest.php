<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Document;

use DateTime;
use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
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
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::toArray
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall
     *
     * @throws DateTimeException
     * @throws Exception
     */
    public function testApplicationInstall(): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser('user')
            ->setKey('null-key')
            ->setExpires(DateTimeUtils::getUtcDateTime('now'))
            ->setNonEncryptedSettings(['lock' => TRUE]);
        $this->pfd($applicationInstall);

        self::assertInstanceOf(DateTime::class, $applicationInstall->getExpires());
        self::assertEquals(['lock' => TRUE], $applicationInstall->getNonEncryptedSettings());
        self::assertEquals(8, count($applicationInstall->toArray()));
    }

}
