<?php declare(strict_types=1);

namespace Tests\Integration\Commons\DatabaseManager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use PDO;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DatabaseManagerLocatorTest
 *
 * @package Tests\Integration\Commons\DatabaseManager
 */
class DatabaseManagerLocatorTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testConnectDocumentManager(): void
    {
        /** @var DocumentManager $documentManager */
        $documentManager = $this->container->get('doctrine_mongodb.odm.default_document_manager');
        $this->assertTrue(is_array($documentManager->getConnection()->listDatabases()));
    }

    /**
     *
     */
    public function testConnectEntityManager(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');

        $query = $entityManager->getConnection()->query('SHOW DATABASES;');
        $query->execute();
        $this->assertTrue(is_array($query->fetchAll(PDO::FETCH_OBJ)));
    }

}