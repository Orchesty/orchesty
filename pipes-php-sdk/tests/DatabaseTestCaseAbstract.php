<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package Tests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var Session
     */
    protected $session;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getClient()->dropDatabase('pipes-php-sdk');
        $this->session = new Session();
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     * @param object $document
     *
     * @throws Exception
     */
    protected function persistAndFlush(object $document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

}
