<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\DatabaseManager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use LogicException;

/**
 * Class DatabaseManagerLocator
 *
 * @package Hanaboso\PipesFramework\Commons\DatabaseManager
 */
class DatabaseManagerLocator implements DatabaseManagerLocatorInterface
{

    /**
     * @var DocumentManager|null
     */
    private $documentManager;

    /**
     * @var EntityManager|null
     */
    private $entityManager;

    /**
     * @var string
     */
    private $type;

    /**
     * DatabaseManagerLocator constructor.
     *
     * @param DocumentManager $documentManager
     * @param EntityManager   $entityManager
     * @param string          $db
     */
    public function __construct(
        ?DocumentManager $documentManager = NULL,
        ?EntityManager $entityManager = NULL,
        string $db
    )
    {
        $this->documentManager = $documentManager;
        $this->entityManager   = $entityManager;
        $this->type            = $db;
    }

    /**
     * @return DocumentManager|EntityManager
     */
    public function get()
    {
        $manager = NULL;
        if ($this->type === 'ODM') {
            $manager = $this->getDm();
        } else if ($this->type === 'ORM') {
            $manager = $this->getEm();
        }

        if (!$manager) {
            throw new LogicException('Database manager not found.');
        }

        return $manager;
    }

    /**
     * @return DocumentManager|null
     */
    public function getDm(): ?DocumentManager
    {
        return $this->documentManager;
    }

    /**
     * @return EntityManager|null
     */
    public function getEm(): ?EntityManager
    {
        return $this->entityManager;
    }

}