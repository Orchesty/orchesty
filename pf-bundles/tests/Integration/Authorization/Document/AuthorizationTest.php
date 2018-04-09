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
    public function testFlushAndLoad(): void
    {
        $token = ['token' => 'token'];
        $settings = ['foo' => 'bar', 'baz' => 'bat'];

        $authorization = new Authorization('magento2_auth');
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
        $this->assertArrayHasKey('encryptedToken', $data[$authorization->getId()]);
        $this->assertInternalType('string', $data[$authorization->getId()]['encryptedToken']);

        $this->assertArrayNotHasKey('settings', $data[$authorization->getId()]);
        $this->assertArrayHasKey('encryptedSettings', $data[$authorization->getId()]);
        $this->assertInternalType('string', $data[$authorization->getId()]['encryptedSettings']);

        // postLoad should decrypt the data
        $loaded = $this->dm->getRepository(Authorization::class)->find($authorization->getId());

        $this->assertNotEmpty($loaded->getToken());
        $this->assertInternalType('array', $loaded->getToken());
        $this->assertEquals($token, $loaded->getToken());

        $this->assertNotEmpty($loaded->getSettings());
        $this->assertInternalType('array', $loaded->getSettings());
        $this->assertEquals($settings, $loaded->getSettings());
    }

}
