<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 3:30 PM
 */

namespace Tests\Integration\EventListener;

use Hanaboso\PipesFramework\Authorizations\Document\AuthorizationToken;
use Hanaboso\PipesFramework\Authorizations\DocumentListener\DocumentListener;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class EventListenerTest
 *
 * @package Tests\Integration\EventListener
 */
class EventListenerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers DocumentListener::postLoad()
     * @covers DocumentListener::preFlush()
     */
    public function testEncryptDecryptData(): void
    {
        $token = new AuthorizationToken();
        $token->setData(['data' => 'data']);
        $this->documentManager->persist($token);
        $this->documentManager->flush();
        $this->documentManager->clear();
        $token = $this->documentManager->getRepository(AuthorizationToken::class)->find($token->getId());

        self::assertNotEmpty($token->getData());
        self::assertTrue(is_array($token->getData()));
    }

}