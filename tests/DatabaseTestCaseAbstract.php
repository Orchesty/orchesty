<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
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
    protected $documentManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * DatabaseTestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();
        self::bootKernel();
        $this->documentManager = $this->container->get('doctrine_mongodb.odm.default_document_manager');
        $this->session         = new Session();
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->documentManager->getConnection()->dropDatabase('pipes');
        $this->session->invalidate();
        $this->session->clear();
    }

    /**
     * @param object $document
     */
    protected function persistAndFlush($document): void
    {
        $this->documentManager->persist($document);
        $this->documentManager->flush($document);
    }

}