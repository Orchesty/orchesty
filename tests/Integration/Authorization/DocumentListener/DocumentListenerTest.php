<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 3:30 PM
 */

namespace Tests\Integration\Authorization\DocumentListener;

use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\DocumentListener\DocumentListener;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DocumentListenerTest
 *
 * @package Tests\Integration\Authorization\DocumentListener
 */
class DocumentListenerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers DocumentListener::postLoad()
     * @covers DocumentListener::preFlush()
     */
    public function testEncryptDecryptToken(): void
    {
        $authorization = new Authorization('magento2.auth');
        $authorization->setToken(['token' => 'token']);
        $this->dm->persist($authorization);
        $this->dm->flush();
        $this->dm->clear();
        $authorization = $this->dm->getRepository(Authorization::class)->find($authorization->getId());

        self::assertNotEmpty($authorization->getToken());
        self::assertTrue(is_array($authorization->getToken()));
        self::assertEquals(['token' => 'token'], $authorization->getToken());
    }

    /**
     * @covers DocumentListener::postLoad()
     * @covers DocumentListener::preFlush()
     */
    public function testEncryptDecryptSettings(): void
    {
        $authorization = new Authorization('magento2.auth');
        $authorization->setSettings(['settings' => 'settings']);
        $this->dm->persist($authorization);
        $this->dm->flush();
        $this->dm->clear();
        $authorization = $this->dm->getRepository(Authorization::class)->find($authorization->getId());

        self::assertNotEmpty($authorization->getSettings());
        self::assertTrue(is_array($authorization->getSettings()));
        self::assertEquals(['settings' => 'settings'], $authorization->getSettings());
    }

}