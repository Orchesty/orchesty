<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\DatabaseManager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;

/**
 * Class UserDatabaseManagerLocator
 *
 * @package Hanaboso\PipesFramework\User\DatabaseManager
 */
class UserDatabaseManagerLocator extends DatabaseManagerLocator
{

    /**
     * @var string
     */
    private $manager;

    /**
     * UserDatabaseManagerLocator constructor.
     *
     * @param DocumentManager $documentManager
     * @param EntityManager   $entityManager
     * @param array           $db
     */
    public function __construct(DocumentManager $documentManager, EntityManager $entityManager, array $db)
    {
        parent::__construct($documentManager, $entityManager);
        parent::__construct($documentManager, $entityManager);
        $this->manager = $db[0];
    }

    /**
     * @return EntityManager|DocumentManager
     */
    public function get()
    {
        if ($this->manager === 'ODM') {
            return $this->getDm();
        }

        return $this->getEm();
    }

}