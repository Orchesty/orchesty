<?php declare(strict_types=1);

namespace PipesPhpSdkTests;

use Exception;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\DatabaseTestTrait;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package PipesPhpSdkTests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    use DatabaseTestTrait;

    /**
     * @var Session<mixed>
     */
    protected Session $session;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session();
        $this->session->invalidate();
        $this->session->clear();

        $this->dm = self::getContainer()->get('doctrine_mongodb.odm.default_document_manager');

        $this->clearMongo();
    }

}
