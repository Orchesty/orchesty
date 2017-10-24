<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Document;

use CleverConnectors\AppBundle\Document\SystemInstall;
use MongoDB\BSON\ObjectID;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SystemInstallTest
 *
 * @package Tests\Integration\AppBundle\Document
 */
final class SystemInstallTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers Authorization
     */
    public function testFlushAndLoad(): void
    {
        $user = 'Clever';
        $settings = ['foo' => 'bar', 'baz' => 'bat'];

        $sys = new SystemInstall();
        $sys->setUser($user);
        $sys->setSettings($settings);

        $this->dm->persist($sys);
        $this->dm->flush();
        $this->dm->clear();

        // Raw data should be encrypted vi preFlush
        $data = $this->dm->getDocumentCollection(SystemInstall::class)->find([
            '_id' => new ObjectID($sys->getId()),
        ])->toArray();

        $this->assertArrayHasKey($sys->getId(), $data);
        $this->assertArrayHasKey('user', $data[$sys->getId()]);
        $this->assertArrayHasKey('encryptedSettings', $data[$sys->getId()]);
        $this->assertArrayNotHasKey('settings', $data[$sys->getId()]);
        $this->assertEquals($user, $data[$sys->getId()]['user']);
        $this->assertInternalType('string', $data[$sys->getId()]['encryptedSettings']);

        // postLoad should decrypt the data
        /** @var SystemInstall $loaded */
        $loaded = $this->dm->getRepository(SystemInstall::class)->find($sys->getId());
        $this->assertEquals($user, $loaded->getUser());
        $this->assertEquals($settings, $loaded->getSettings());
    }

}
