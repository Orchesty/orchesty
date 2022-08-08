<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Document;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use MongoDB\BSON\ObjectId;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class InstallTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Document
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

        $data = $this->dm->getDocumentCollection(ApplicationInstall::class)->find(
            [
                '_id' => new ObjectID($applicationInstall->getId()),
            ],
        )->toArray();
        $data = reset($data);

        self::assertEquals($applicationInstall->getId(), $data['_id']);
        self::assertArrayHasKey('user', $data);
        self::assertArrayHasKey('encryptedSettings', $data);

        /** @var ApplicationInstall $loaded */
        $loaded = $this->dm->getRepository(ApplicationInstall::class)->find($applicationInstall->getId());
        self::assertNotEmpty($loaded->getSettings());
    }

}
