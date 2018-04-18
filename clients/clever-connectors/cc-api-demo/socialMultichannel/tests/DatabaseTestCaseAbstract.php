<?php declare(strict_types=1);

namespace Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;

/**
 * Class DatabaseTestCaseAbstract
 *
 * @package Tests
 */
abstract class DatabaseTestCaseAbstract extends ContainerTestCaseAbstract
{

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * ContainerTestCaseAbstract constructor.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = NULL, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        /** @var EntityManager $em */
        $em = $this->container->getByType(EntityManager::class);
        /** @var DocumentManager $dm */
        $dm       = $this->container->getByType(DocumentManager::class);
        $this->em = $em;
        $this->dm = $dm;
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->em->getConnection()->exec('SET FOREIGN_KEY_CHECKS=0;');
        $this->em->getConnection()->exec('TRUNCATE TABLE audience;');
        $this->em->getConnection()->exec('TRUNCATE TABLE ad;');
        $this->em->getConnection()->exec('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * @param object $document
     */
    protected function persistAndFlushDocument($document): void
    {
        $this->dm->persist($document);
        $this->dm->flush($document);
    }

    /**
     * @param object $entity
     */
    protected function persistAndFlushEntity($entity): void
    {
        $this->em->persist($entity);
        $this->em->flush($entity);
    }

}
