<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\DatabaseManager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;

/**
 * Interface DatabaseManagerLocatorInterface
 *
 * @package Hanaboso\PipesFramework\Commons\DatabaseManager
 */
interface DatabaseManagerLocatorInterface
{

    /**
     * @return DocumentManager
     */
    public function getDm(): DocumentManager;

    /**
     * @return EntityManager
     */
    public function getEm(): EntityManager;

}