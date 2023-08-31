<?php declare(strict_types=1);

namespace NotificationSenderTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\ControllerTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ControllerTestCaseAbstract
 *
 * @package NotificationSenderTests
 */
abstract class ControllerTestCaseAbstract extends WebTestCase
{

    use DatabaseTestTrait;
    use ControllerTestTrait;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->startClient();
        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
    }

}
