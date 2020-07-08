<?php declare(strict_types=1);

namespace HbPFConnectorsTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\PrivateTrait;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package HbPFConnectorsTests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    use PrivateTrait;
    use CustomAssertTrait;
    use DatabaseTestTrait;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->clearMongo();
    }

}
