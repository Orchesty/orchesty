<?php declare(strict_types=1);

namespace ApplinthTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package ApplinthTests
 */
abstract class DatabaseTestCaseAbstract extends KernelTestCaseAbstract
{

    /**
     * @var DocumentManager
     */
    protected DocumentManager $dm;

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
    protected function persistAndFlush(object $document): void
    {
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @return string
     */
    private function getMongoDatabaseName(): string
    {
        return sprintf('%s%s', $this->dm->getConfiguration()->getDefaultDB(), (string) getenv('TEST_TOKEN'));
    }

}
