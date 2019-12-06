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
     * @var Session<mixed>
     */
    protected $session;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->session = new Session();
        $this->session->invalidate();
        $this->session->clear();

        $this->dm = self::$container->get('doctrine_mongodb.odm.default_document_manager');
        $this->dm->getConfiguration()->setDefaultDB($this->getMongoDatabaseName());

        $documents = $this->dm->getMetadataFactory()->getAllMetadata();
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document->getName())->drop();
        }
    }

    /**
     * @param object $document
     *
     * @throws Exception
     */
    protected function persistAndFlush($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @return string
     */
    private function getMongoDatabaseName(): string
    {
        return sprintf('%s%s', $this->dm->getConfiguration()->getDefaultDB(), getenv('TEST_TOKEN'));
    }

}
