<?php declare(strict_types=1);

namespace Tests\Integration\Authorization\Document;

use Exception;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use MongoDB\BSON\ObjectId;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class InstallTest
 *
 * @package Tests\Integration\Authorization\Document
 */
final class InstallTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    function testFlushAndLoad(): void
    {
        $settings = ['foo' => 'bar', 'baz' => 'bat'];

        $applicationInstall = new ApplicationInstall();
        $applicationInstall->setUser('UserExample');
        $applicationInstall->setSettings($settings);

        $this->dm->persist($applicationInstall);
        $this->dm->flush();
        $this->dm->clear();

        $data = $this->dm->getDocumentCollection(ApplicationInstall::class)->find([
            '_id' => new ObjectID($applicationInstall->getId()),
        ])->toArray();

        self::assertArrayHasKey($applicationInstall->getId(), $data);
        self::assertArrayHasKey('user', $data[$applicationInstall->getId()]);
        self::assertArrayHasKey('encryptedSettings', $data[$applicationInstall->getId()]);

        /** @var ApplicationInstall $loaded */
        $loaded = $this->dm->getRepository(ApplicationInstall::class)->find($applicationInstall->getId());
        self::assertNotEmpty($loaded->getSettings());
    }

}
