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
    public function testEncryptDecryptData(): void
    {
        $token = new Authorization('magento2.auth');
        $token->setToken(['data' => 'data']);
        $this->dm->persist($token);
        $this->dm->flush();
        $this->dm->clear();
        $token = $this->dm->getRepository(Authorization::class)->find($token->getId());

        self::assertNotEmpty($token->getToken());
        self::assertTrue(is_array($token->getToken()));
    }

}