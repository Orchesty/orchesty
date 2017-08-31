<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\DatabaseManager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;

/**
 * Class DatabaseManagerLocator
 *
 * @package Hanaboso\PipesFramework\Commons\DatabaseManager
 */
class DatabaseManagerLocator implements DatabaseManagerLocatorInterface
{

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * DatabaseManagerLocator constructor.
     *
     * @param DocumentManager $documentManager
     * @param EntityManager   $entityManager
     */
    public function __construct(DocumentManager $documentManager, EntityManager $entityManager)
    {
        $this->documentManager = $documentManager;
        $this->entityManager   = $entityManager;
    }

    /**
     * @return DocumentManager
     */
    public function getDm(): DocumentManager
    {
        return $this->documentManager;
    }

    /**
     * @return EntityManager
     */
    public function getEm(): EntityManager
    {
        return $this->entityManager;
    }

}