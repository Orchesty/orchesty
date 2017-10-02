<?php declare(strict_types=1);

namespace Tests\Integration\Authorization\Document;

use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use MongoDB\BSON\ObjectID;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DocumentListenerTest
 *
 * @package Tests\Integration\Authorization\DocumentListener
 */
class AuthorizationTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers Authorization
     */
    public function testPersistAndLoad(): void
    {
        $token = ['token' => 'token'];
        $settings = ['foo' => 'bar', 'baz' => 'bat'];

        $authorization = new Authorization('magento2.auth');
        $authorization->setToken($token);
        $authorization->setSettings($settings);

        $this->dm->persist($authorization);
        $this->dm->flush();
        $this->dm->clear();

        // Raw data should be encrypted vi preFlush
        $data = $this->dm->getDocumentCollection(Authorization::class)->find([
            '_id' => new ObjectID($authorization->getId()),
        ])->toArray();

        $this->assertArrayHasKey($authorization->getId(), $data);
        $this->assertArrayNotHasKey('token', $data[$authorization->getId()]);
        $this->assertArrayNotHasKey('settings', $data[$authorization->getId()]);
        $this->assertInternalType('string', $data[$authorization->getId()]['encryptedToken']);
        $this->assertInternalType('string', $data[$authorization->getId()]['encryptedSettings']);

        // postLoad should decrypt the data
        $loaded = $this->dm->getRepository(Authorization::class)->find($authorization->getId());

        self::assertNotEmpty($loaded->getToken());
        self::assertTrue(is_array($loaded->getToken()));
        self::assertEquals($token, $loaded->getToken());

        self::assertNotEmpty($loaded->getSettings());
        self::assertTrue(is_array($loaded->getSettings()));
        self::assertEquals($settings, $loaded->getSettings());
    }

}
