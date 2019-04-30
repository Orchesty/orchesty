<?php declare(strict_types=1);

namespace Tests\Integration\Authorization\Document;

use Exception;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use MongoDB\BSON\ObjectID;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class AuthorizationTest
 *
 * @package Tests\Integration\Authorization\Document
 */
final class AuthorizationTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testFlushAndLoad(): void
    {
        $token    = ['token' => 'token'];
        $settings = ['foo' => 'bar', 'baz' => 'bat'];

        $authorization = new Authorization('null');
        $authorization->setToken($token);
        $authorization->setSettings($settings);

        $this->dm->persist($authorization);
        $this->dm->flush();
        $this->dm->clear();

        // Raw data should be encrypted vi preFlush
        $data = $this->dm->getDocumentCollection(Authorization::class)->find([
            '_id' => new ObjectID($authorization->getId()),
        ])->toArray();

        self::assertArrayHasKey($authorization->getId(), $data);

        self::assertArrayNotHasKey('token', $data[$authorization->getId()]);
        self::assertArrayHasKey('encryptedToken', $data[$authorization->getId()]);
        self::assertTrue(is_string($data[$authorization->getId()]['encryptedToken']));

        self::assertArrayNotHasKey('settings', $data[$authorization->getId()]);
        self::assertArrayHasKey('encryptedSettings', $data[$authorization->getId()]);
        self::assertTrue(is_string($data[$authorization->getId()]['encryptedSettings']));

    }

}
