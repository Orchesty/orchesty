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
        $this->assertArrayHasKey('token', $data[$authorization->getId()]);
        $this->assertArrayHasKey('settings', $data[$authorization->getId()]);
        $this->assertInternalType('string', $data[$authorization->getId()]['token']);
        $this->assertInternalType('string', $data[$authorization->getId()]['settings']);

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
